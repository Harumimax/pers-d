<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrepareGameService
{
    /**
     * @param array{mode:string,direction:string,dictionary_ids:array<int,int|string>,ready_dictionary_ids?:array<int,int|string>,parts_of_speech:array<int,string>,words_count:int} $config
     * @return array{gameSession: GameSession, notice: string|null}
     */
    public function prepare(User $user, array $config): array
    {
        $dictionaryIds = collect($config['dictionary_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $readyDictionaryIds = collect($config['ready_dictionary_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $ownedDictionaryIds = $user->dictionaries()
            ->whereIn('id', $dictionaryIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        sort($dictionaryIds);
        sort($ownedDictionaryIds);

        if ($dictionaryIds !== $ownedDictionaryIds) {
            throw ValidationException::withMessages([
                'dictionary_ids' => __('remainder.messages.start.not_owner'),
            ]);
        }

        $availableReadyDictionaryIds = ReadyDictionary::query()
            ->whereIn('id', $readyDictionaryIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        sort($readyDictionaryIds);
        sort($availableReadyDictionaryIds);

        if ($readyDictionaryIds !== $availableReadyDictionaryIds) {
            throw ValidationException::withMessages([
                'ready_dictionary_ids' => __('remainder.messages.start.ready_not_found'),
            ]);
        }

        $partsOfSpeech = $this->normalizePartsOfSpeech($config['parts_of_speech']);
        $availableWords = $this->availableWordCandidates($user->id, $dictionaryIds, $readyDictionaryIds, $partsOfSpeech);

        if ($availableWords->isEmpty()) {
            throw ValidationException::withMessages([
                'dictionary_ids' => __('remainder.messages.start.no_words'),
            ]);
        }

        $requestedWordsCount = (int) $config['words_count'];
        $selectedWords = $availableWords
            ->shuffle()
            ->take(min($requestedWordsCount, $availableWords->count()))
            ->values();

        $notice = null;
        if ($selectedWords->count() < $requestedWordsCount) {
            $notice = __('remainder.messages.start.partial_notice', [
                'count' => $selectedWords->count(),
                'word_label' => $selectedWords->count() === 1
                    ? __('remainder.messages.start.word_label_singular')
                    : __('remainder.messages.start.word_label_plural'),
            ]);
        }

        $baseItemPayloads = $selectedWords
            ->values()
            ->map(function (array $word, int $index) use ($config): array {
                return [
                    'source' => $word['source'],
                    'word_id' => $word['word_id'],
                    'word' => $word['word'],
                    'translation' => $word['translation'],
                    'comment' => $word['comment'],
                    'order_index' => $index + 1,
                    'prompt_text' => $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['word']
                        : $word['translation'],
                    'part_of_speech_snapshot' => $word['part_of_speech'],
                    'correct_answer' => $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['translation']
                        : $word['word'],
                    'options_json' => null,
                    'user_answer' => null,
                    'is_correct' => null,
                    'answered_at' => null,
                ];
            });

        $warnings = [];
        $itemPayloads = $baseItemPayloads;

        if ($config['mode'] === GameSession::MODE_CHOICE) {
            $availableAnswers = $availableWords
                ->map(function (array $word) use ($config): string {
                    return $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['translation']
                        : $word['word'];
                })
                ->values();

            $choicePayload = app(ChoiceOptionsBuilder::class)->build($baseItemPayloads, $availableAnswers);
            $itemPayloads = $choicePayload['items'];
            $warnings = $choicePayload['warnings'];
        }

        /** @var GameSession $gameSession */
        $gameSession = DB::transaction(function () use ($user, $config, $dictionaryIds, $readyDictionaryIds, $partsOfSpeech, $requestedWordsCount, $selectedWords, $warnings, $itemPayloads): GameSession {
            $session = GameSession::create([
                'user_id' => $user->id,
                'mode' => $config['mode'],
                'direction' => $config['direction'],
                'total_words' => $selectedWords->count(),
                'correct_answers' => 0,
                'status' => GameSession::STATUS_ACTIVE,
                'started_at' => now(),
                'finished_at' => null,
                'config_snapshot' => [
                    'mode' => $config['mode'],
                    'direction' => $config['direction'],
                    'requested_words_count' => $requestedWordsCount,
                    'actual_words_count' => $selectedWords->count(),
                    'dictionary_ids' => $dictionaryIds,
                    'ready_dictionary_ids' => $readyDictionaryIds,
                    'parts_of_speech' => $partsOfSpeech,
                    'warnings' => $warnings,
                    'options_target_count' => $config['mode'] === GameSession::MODE_CHOICE ? 6 : null,
                ],
            ]);

            $items = $itemPayloads
                ->values()
                ->map(function (array $item) use ($session): array {
                    $wordId = $item['word_id'];

                    if ($wordId === null) {
                        $word = Word::create([
                            'word' => $item['word'],
                            'translation' => $item['translation'],
                            'part_of_speech' => $item['part_of_speech_snapshot'],
                            'comment' => $item['comment'],
                        ]);

                        $wordId = $word->id;
                    }

                    return [
                        'game_session_id' => $session->id,
                        'word_id' => $wordId,
                        'order_index' => $item['order_index'],
                        'prompt_text' => $item['prompt_text'],
                        'part_of_speech_snapshot' => $item['part_of_speech_snapshot'],
                        'correct_answer' => $item['correct_answer'],
                        'options_json' => $item['options_json'] !== null
                            ? json_encode($item['options_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : null,
                        'user_answer' => $item['user_answer'],
                        'is_correct' => $item['is_correct'],
                        'answered_at' => $item['answered_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->all();

            GameSessionItem::insert($items);

            return $session->fresh();
        });

        return [
            'gameSession' => $gameSession,
            'notice' => $notice,
        ];
    }

    /**
     * @param array<int, string> $partsOfSpeech
     * @return array<int, string>
     */
    private function normalizePartsOfSpeech(array $partsOfSpeech): array
    {
        $normalized = collect($partsOfSpeech)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->contains('all') || $normalized->isEmpty()) {
            return ['all'];
        }

        return $normalized->all();
    }

    /**
     * @param array<int, int> $dictionaryIds
     * @param array<int, int> $readyDictionaryIds
     * @param array<int, string> $partsOfSpeech
     * @return Collection<int, array{source:string,word_id:int|null,word:string,translation:string,part_of_speech:?string,comment:?string}>
     */
    private function availableWordCandidates(int $userId, array $dictionaryIds, array $readyDictionaryIds, array $partsOfSpeech): Collection
    {
        $userWords = collect();

        if ($dictionaryIds !== []) {
            $query = Word::query()
                ->select('words.*')
                ->join('user_dictionary_word', 'user_dictionary_word.word_id', '=', 'words.id')
                ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                ->where('user_dictionaries.user_id', $userId)
                ->whereIn('user_dictionaries.id', $dictionaryIds)
                ->distinct();

            if ($partsOfSpeech !== ['all']) {
                $query->whereIn('words.part_of_speech', $partsOfSpeech);
            }

            $userWords = $query->get()
                ->unique('id')
                ->values()
                ->map(static fn (Word $word): array => [
                    'source' => 'user',
                    'word_id' => $word->id,
                    'word' => $word->word,
                    'translation' => $word->translation,
                    'part_of_speech' => $word->part_of_speech,
                    'comment' => $word->comment,
                ]);
        }

        $readyWords = collect();

        if ($readyDictionaryIds !== []) {
            $query = ReadyDictionaryWord::query()
                ->whereIn('ready_dictionary_id', $readyDictionaryIds);

            if ($partsOfSpeech !== ['all']) {
                $query->whereIn('part_of_speech', $partsOfSpeech);
            }

            $readyWords = $query->get()
                ->map(static fn (ReadyDictionaryWord $word): array => [
                    'source' => 'ready',
                    'word_id' => null,
                    'word' => $word->word,
                    'translation' => $word->translation,
                    'part_of_speech' => $word->part_of_speech,
                    'comment' => $word->comment,
                ]);
        }

        return $userWords
            ->concat($readyWords)
            ->values();
    }
}
