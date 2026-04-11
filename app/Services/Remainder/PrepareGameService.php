<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrepareGameService
{
    /**
     * @param array{mode:string,direction:string,dictionary_ids:array<int,int|string>,parts_of_speech:array<int,string>,words_count:int} $config
     * @return array{gameSession: GameSession, notice: string|null}
     */
    public function prepare(User $user, array $config): array
    {
        $dictionaryIds = collect($config['dictionary_ids'])
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
                'dictionary_ids' => 'You can use only your own dictionaries in the game configuration.',
            ]);
        }

        $partsOfSpeech = $this->normalizePartsOfSpeech($config['parts_of_speech']);
        $availableWords = $this->availableWords($user->id, $dictionaryIds, $partsOfSpeech);

        if ($availableWords->isEmpty()) {
            throw ValidationException::withMessages([
                'dictionary_ids' => 'No words matched the selected dictionaries and filters.',
            ]);
        }

        $requestedWordsCount = (int) $config['words_count'];
        $selectedWords = $availableWords
            ->shuffle()
            ->take(min($requestedWordsCount, $availableWords->count()))
            ->values();

        $notice = null;
        if ($selectedWords->count() < $requestedWordsCount) {
            $notice = sprintf(
                'Only %d %s matched the current filters, so the game was created with %d.',
                $selectedWords->count(),
                $selectedWords->count() === 1 ? 'word' : 'words',
                $selectedWords->count(),
            );
        }

        $baseItemPayloads = $selectedWords
            ->values()
            ->map(function (Word $word, int $index) use ($config): array {
                return [
                    'word_id' => $word->id,
                    'order_index' => $index + 1,
                    'prompt_text' => $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word->word
                        : $word->translation,
                    'correct_answer' => $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word->translation
                        : $word->word,
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
                ->map(function (Word $word) use ($config): string {
                    return $config['direction'] === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word->translation
                        : $word->word;
                })
                ->values();

            $choicePayload = app(ChoiceOptionsBuilder::class)->build($baseItemPayloads, $availableAnswers);
            $itemPayloads = $choicePayload['items'];
            $warnings = $choicePayload['warnings'];
        }

        /** @var GameSession $gameSession */
        $gameSession = DB::transaction(function () use ($user, $config, $dictionaryIds, $partsOfSpeech, $requestedWordsCount, $selectedWords, $warnings, $itemPayloads): GameSession {
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
                    'parts_of_speech' => $partsOfSpeech,
                    'warnings' => $warnings,
                    'options_target_count' => $config['mode'] === GameSession::MODE_CHOICE ? 6 : null,
                ],
            ]);

            $items = $itemPayloads
                ->values()
                ->map(function (array $item) use ($session): array {
                    return [
                        'game_session_id' => $session->id,
                        'word_id' => $item['word_id'],
                        'order_index' => $item['order_index'],
                        'prompt_text' => $item['prompt_text'],
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
     * @param array<int, string> $partsOfSpeech
     * @return Collection<int, Word>
     */
    private function availableWords(int $userId, array $dictionaryIds, array $partsOfSpeech): Collection
    {
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

        /** @var Collection<int, Word> $words */
        $words = $query->get()->unique('id')->values();

        return $words;
    }
}
