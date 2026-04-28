<?php

namespace App\Services\Remainder\Core;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GameWordSelectionService
{
    /**
     * @return Collection<int, array{source:string,word_id:int|null,word:string,translation:string,part_of_speech:?string,comment:?string,remainder_had_mistake:bool}>
     */
    public function availableWordCandidates(?User $user, GameSessionConfigData $config): Collection
    {
        $this->assertDictionaryAccess($user, $config);

        $userWords = collect();

        if ($user !== null && $config->dictionaryIds !== []) {
            $query = Word::query()
                ->select('words.*')
                ->join('user_dictionary_word', 'user_dictionary_word.word_id', '=', 'words.id')
                ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                ->where('user_dictionaries.user_id', $user->id)
                ->whereIn('user_dictionaries.id', $config->dictionaryIds)
                ->distinct();

            if ($config->partsOfSpeech !== ['all']) {
                $query->whereIn('words.part_of_speech', $config->partsOfSpeech);
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
                    'remainder_had_mistake' => $word->remainder_had_mistake,
                ]);
        }

        $readyWords = collect();

        if ($config->readyDictionaryIds !== []) {
            $query = ReadyDictionaryWord::query()
                ->whereIn('ready_dictionary_id', $config->readyDictionaryIds);

            if ($config->partsOfSpeech !== ['all']) {
                $query->whereIn('part_of_speech', $config->partsOfSpeech);
            }

            $readyWords = $query->get()
                ->map(static fn (ReadyDictionaryWord $word): array => [
                    'source' => 'ready',
                    'word_id' => null,
                    'word' => $word->word,
                    'translation' => $word->translation,
                    'part_of_speech' => $word->part_of_speech,
                    'comment' => $word->comment,
                    'remainder_had_mistake' => false,
                ]);
        }

        return $userWords
            ->concat($readyWords)
            ->values();
    }

    /**
     * @param Collection<int, array{source:string,word_id:int|null,word:string,translation:string,part_of_speech:?string,comment:?string,remainder_had_mistake:bool}> $availableWords
     * @return Collection<int, array{source:string,word_id:int|null,word:string,translation:string,part_of_speech:?string,comment:?string,remainder_had_mistake:bool}>
     */
    public function selectWordsForSession(Collection $availableWords, int $targetWordsCount): Collection
    {
        if ($targetWordsCount === 0) {
            return collect();
        }

        $mistakeWords = $availableWords
            ->filter(static fn (array $word): bool => $word['remainder_had_mistake'] === true)
            ->shuffle()
            ->values();

        $cleanWords = $availableWords
            ->filter(static fn (array $word): bool => $word['remainder_had_mistake'] === false)
            ->shuffle()
            ->values();

        $mistakeTargetCount = min(
            $mistakeWords->count(),
            (int) floor($targetWordsCount * 0.5),
        );

        $selectedMistakeWords = $mistakeWords
            ->take($mistakeTargetCount)
            ->values();

        $remainingCount = $targetWordsCount - $selectedMistakeWords->count();

        $selectedCleanWords = $cleanWords
            ->take($remainingCount)
            ->values();

        $remainingCount -= $selectedCleanWords->count();

        $additionalMistakeWords = $mistakeWords
            ->slice($selectedMistakeWords->count())
            ->take($remainingCount)
            ->values();

        return $selectedMistakeWords
            ->concat($selectedCleanWords)
            ->concat($additionalMistakeWords)
            ->shuffle()
            ->values();
    }

    private function assertDictionaryAccess(?User $user, GameSessionConfigData $config): void
    {
        $ownedDictionaryIds = [];

        if ($user !== null && $config->dictionaryIds !== []) {
            $ownedDictionaryIds = $user->dictionaries()
                ->whereIn('id', $config->dictionaryIds)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();
        }

        if ($config->dictionaryIds !== $ownedDictionaryIds) {
            throw ValidationException::withMessages([
                'dictionary_ids' => __('remainder.messages.start.not_owner'),
            ]);
        }

        $availableReadyDictionaryIds = ReadyDictionary::query()
            ->whereIn('id', $config->readyDictionaryIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($config->readyDictionaryIds !== $availableReadyDictionaryIds) {
            throw ValidationException::withMessages([
                'ready_dictionary_ids' => __('remainder.messages.start.ready_not_found'),
            ]);
        }
    }
}
