<?php

namespace App\Services\Favorites;

use App\Models\FavoriteWord;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Models\WordExample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FavoriteWordsService
{
    public const VIRTUAL_DICTIONARY_SLUG = 'favorites';
    public const VIRTUAL_DICTIONARY_ID = -1;

    public function queryForUser(User|int $user): Builder
    {
        return FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function countForUser(User|int $user, ?string $language = null): int
    {
        $query = $language === null
            ? $this->queryForUser($user)
            : $this->favoriteWordEntriesQuery($user)->whereRaw(
                'COALESCE(favorite_user_dictionaries.language, favorite_ready_dictionaries.language) = ?',
                [$language],
            );

        return (int) $query->count();
    }

    /**
     * @return array{name:string, slug:string, count:int, is_clickable:bool}
     */
    public function virtualDictionarySummaryForUser(User|int $user, ?string $language = null): array
    {
        $count = $this->countForUser($user, $language);

        return [
            'name' => __('dictionaries.index.favorites.name'),
            'slug' => self::VIRTUAL_DICTIONARY_SLUG,
            'count' => $count,
            'is_clickable' => $count > 0,
        ];
    }

    public function virtualDictionaryId(): int
    {
        return self::VIRTUAL_DICTIONARY_ID;
    }

    public function isVirtualDictionaryId(int|string|null $id): bool
    {
        return (int) $id === self::VIRTUAL_DICTIONARY_ID;
    }

    /**
     * @param iterable<int> $wordIds
     * @return array<int, bool>
     */
    public function userDictionaryFavoriteStateMap(User|int $user, UserDictionary|int $dictionary, iterable $wordIds): array
    {
        $ids = collect($wordIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $favoriteIds = FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_USER)
            ->where('source_dictionary_id', $this->resolveDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_USER)
            ->whereIn('source_word_id', $ids->all())
            ->pluck('source_word_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return $ids
            ->mapWithKeys(static fn (int $id): array => [$id => in_array($id, $favoriteIds, true)])
            ->all();
    }

    /**
     * @param iterable<int> $wordIds
     * @return array<int, bool>
     */
    public function readyDictionaryFavoriteStateMap(User|int $user, ReadyDictionary|int $dictionary, iterable $wordIds): array
    {
        $ids = collect($wordIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $favoriteIds = FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_READY)
            ->where('source_dictionary_id', $this->resolveReadyDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_READY)
            ->whereIn('source_word_id', $ids->all())
            ->pluck('source_word_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return $ids
            ->mapWithKeys(static fn (int $id): array => [$id => in_array($id, $favoriteIds, true)])
            ->all();
    }

    public function favoriteWordEntriesQuery(User|int $user): Builder
    {
        $userId = $this->resolveUserId($user);

        return FavoriteWord::query()
            ->where('favorite_words.user_id', $userId)
            ->leftJoin('user_dictionaries as favorite_user_dictionaries', function ($join): void {
                $join->on('favorite_user_dictionaries.id', '=', 'favorite_words.source_dictionary_id')
                    ->where('favorite_words.source_dictionary_type', '=', FavoriteWord::SOURCE_DICTIONARY_USER);
            })
            ->leftJoin('ready_dictionaries as favorite_ready_dictionaries', function ($join): void {
                $join->on('favorite_ready_dictionaries.id', '=', 'favorite_words.source_dictionary_id')
                    ->where('favorite_words.source_dictionary_type', '=', FavoriteWord::SOURCE_DICTIONARY_READY);
            })
            ->leftJoin('words as favorite_source_words', function ($join): void {
                $join->on('favorite_source_words.id', '=', 'favorite_words.source_word_id')
                    ->where('favorite_words.source_word_type', '=', FavoriteWord::SOURCE_WORD_USER);
            })
            ->leftJoin('ready_dictionary_words as favorite_ready_words', function ($join): void {
                $join->on('favorite_ready_words.id', '=', 'favorite_words.source_word_id')
                    ->where('favorite_words.source_word_type', '=', FavoriteWord::SOURCE_WORD_READY);
            })
            ->leftJoin('user_word_progress as favorite_user_word_progress', function ($join) use ($userId): void {
                $join->on('favorite_user_word_progress.word_id', '=', 'favorite_source_words.id')
                    ->where('favorite_user_word_progress.user_id', '=', $userId);
            })
            ->where(function ($query): void {
                $query->whereNotNull('favorite_source_words.id')
                    ->orWhereNotNull('favorite_ready_words.id');
            })
            ->select([
                'favorite_words.id as favorite_id',
                'favorite_words.source_dictionary_type',
                'favorite_words.source_dictionary_id',
                'favorite_words.source_word_type',
                'favorite_words.source_word_id',
                'favorite_words.created_at as favorite_created_at',
            ])
            ->selectRaw('COALESCE(favorite_source_words.word, favorite_ready_words.word) as word')
            ->selectRaw('COALESCE(favorite_source_words.translation, favorite_ready_words.translation) as translation')
            ->selectRaw('COALESCE(favorite_source_words.comment, favorite_ready_words.comment) as comment')
            ->selectRaw('COALESCE(favorite_source_words.part_of_speech, favorite_ready_words.part_of_speech) as part_of_speech')
            ->selectRaw('COALESCE(favorite_user_dictionaries.name, favorite_ready_dictionaries.name) as source_dictionary_name')
            ->selectRaw('COALESCE(favorite_user_dictionaries.language, favorite_ready_dictionaries.language) as source_dictionary_language')
            ->selectRaw('COALESCE(favorite_user_word_progress.remainder_had_mistake, false) as source_remainder_had_mistake');
    }

    public function favoritesPageQuery(User|int $user): Builder
    {
        return $this->favoriteWordEntriesQuery($user);
    }

    /**
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    public function attachExamplesToFavoritesPageItems(Collection $items): Collection
    {
        $userWordIds = $items
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_USER)
            ->pluck('source_word_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $readyWordIds = $items
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_READY)
            ->pluck('source_word_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $userExamples = $userWordIds->isEmpty()
            ? collect()
            : WordExample::query()
                ->where('exampleable_type', Word::class)
                ->whereIn('exampleable_id', $userWordIds->all())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('exampleable_id');

        $readyExamples = $readyWordIds->isEmpty()
            ? collect()
            : WordExample::query()
                ->where('exampleable_type', ReadyDictionaryWord::class)
                ->whereIn('exampleable_id', $readyWordIds->all())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('exampleable_id');

        return $items->map(function (object $item) use ($userExamples, $readyExamples): object {
            $examples = $item->source_word_type === FavoriteWord::SOURCE_WORD_READY
                ? ($readyExamples->get((int) $item->source_word_id) ?? collect())
                : ($userExamples->get((int) $item->source_word_id) ?? collect());

            $item->examples = $examples;

            return $item;
        });
    }

    /**
     * @param array<int, string> $partsOfSpeech
     * @return Collection<int, array{source:string,word_id:int|null,source_word_id:int,word:string,translation:string,part_of_speech:?string,comment:?string,remainder_had_mistake:bool,dictionary_id:int,dictionary_name:string,language:?string}>
     */
    public function favoriteWordCandidates(User|int $user, ?string $language = null, array $partsOfSpeech = ['all']): Collection
    {
        $query = $this->favoriteWordEntriesQuery($user);

        if ($language !== null) {
            $query->whereRaw(
                'COALESCE(favorite_user_dictionaries.language, favorite_ready_dictionaries.language) = ?',
                [$language],
            );
        }

        if ($partsOfSpeech !== ['all']) {
            $query->whereIn(
                DB::raw('COALESCE(favorite_source_words.part_of_speech, favorite_ready_words.part_of_speech)'),
                $partsOfSpeech,
            );
        }

        return $query
            ->get()
            ->map(function ($favorite): array {
                $isUserSource = $favorite->source_dictionary_type === FavoriteWord::SOURCE_DICTIONARY_USER;

                return [
                    'source' => $isUserSource ? 'user' : 'ready',
                    'word_id' => $isUserSource ? (int) $favorite->source_word_id : null,
                    'source_word_id' => (int) $favorite->source_word_id,
                    'word' => (string) $favorite->word,
                    'translation' => (string) $favorite->translation,
                    'part_of_speech' => $favorite->part_of_speech !== null && trim((string) $favorite->part_of_speech) !== ''
                        ? (string) $favorite->part_of_speech
                        : null,
                    'comment' => $favorite->comment !== null && trim((string) $favorite->comment) !== ''
                        ? (string) $favorite->comment
                        : null,
                    'remainder_had_mistake' => $isUserSource
                        ? (bool) $favorite->source_remainder_had_mistake
                        : false,
                    'dictionary_id' => (int) $favorite->source_dictionary_id,
                    'dictionary_name' => (string) $favorite->source_dictionary_name,
                    'language' => $favorite->source_dictionary_language !== null
                        ? (string) $favorite->source_dictionary_language
                        : null,
                ];
            })
            ->unique(static fn (array $word): string => implode(':', [
                $word['source'],
                (string) $word['dictionary_id'],
                (string) ($word['word_id'] ?? 0),
                $word['word'],
                $word['translation'],
            ]))
            ->values();
    }

    public function addUserDictionaryWord(User|int $user, UserDictionary|int $dictionary, Word|int $word): FavoriteWord
    {
        $userId = $this->resolveUserId($user);
        $dictionaryId = $this->resolveDictionaryId($dictionary);
        $wordId = $this->resolveWordId($word);

        $this->assertUserDictionaryContainsWord($dictionaryId, $wordId);

        return FavoriteWord::query()->firstOrCreate([
            'user_id' => $userId,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_USER,
            'source_dictionary_id' => $dictionaryId,
            'source_word_type' => FavoriteWord::SOURCE_WORD_USER,
            'source_word_id' => $wordId,
        ]);
    }

    public function addReadyDictionaryWord(User|int $user, ReadyDictionary|int $dictionary, ReadyDictionaryWord|int $word): FavoriteWord
    {
        $userId = $this->resolveUserId($user);
        $dictionaryId = $this->resolveReadyDictionaryId($dictionary);
        $wordId = $this->resolveReadyDictionaryWordId($word);

        $this->assertReadyDictionaryContainsWord($dictionaryId, $wordId);

        return FavoriteWord::query()->firstOrCreate([
            'user_id' => $userId,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_READY,
            'source_dictionary_id' => $dictionaryId,
            'source_word_type' => FavoriteWord::SOURCE_WORD_READY,
            'source_word_id' => $wordId,
        ]);
    }

    public function removeUserDictionaryWord(User|int $user, UserDictionary|int $dictionary, Word|int $word): int
    {
        return FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_USER)
            ->where('source_dictionary_id', $this->resolveDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_USER)
            ->where('source_word_id', $this->resolveWordId($word))
            ->delete();
    }

    public function removeReadyDictionaryWord(User|int $user, ReadyDictionary|int $dictionary, ReadyDictionaryWord|int $word): int
    {
        return FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_READY)
            ->where('source_dictionary_id', $this->resolveReadyDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_READY)
            ->where('source_word_id', $this->resolveReadyDictionaryWordId($word))
            ->delete();
    }

    public function toggleUserDictionaryWord(User|int $user, UserDictionary|int $dictionary, Word|int $word): bool
    {
        if ($this->isUserDictionaryWordFavorite($user, $dictionary, $word)) {
            $this->removeUserDictionaryWord($user, $dictionary, $word);

            return false;
        }

        $this->addUserDictionaryWord($user, $dictionary, $word);

        return true;
    }

    public function toggleReadyDictionaryWord(User|int $user, ReadyDictionary|int $dictionary, ReadyDictionaryWord|int $word): bool
    {
        if ($this->isReadyDictionaryWordFavorite($user, $dictionary, $word)) {
            $this->removeReadyDictionaryWord($user, $dictionary, $word);

            return false;
        }

        $this->addReadyDictionaryWord($user, $dictionary, $word);

        return true;
    }

    public function isUserDictionaryWordFavorite(User|int $user, UserDictionary|int $dictionary, Word|int $word): bool
    {
        return FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_USER)
            ->where('source_dictionary_id', $this->resolveDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_USER)
            ->where('source_word_id', $this->resolveWordId($word))
            ->exists();
    }

    public function isReadyDictionaryWordFavorite(User|int $user, ReadyDictionary|int $dictionary, ReadyDictionaryWord|int $word): bool
    {
        return FavoriteWord::query()
            ->where('user_id', $this->resolveUserId($user))
            ->where('source_dictionary_type', FavoriteWord::SOURCE_DICTIONARY_READY)
            ->where('source_dictionary_id', $this->resolveReadyDictionaryId($dictionary))
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_READY)
            ->where('source_word_id', $this->resolveReadyDictionaryWordId($word))
            ->exists();
    }

    private function resolveUserId(User|int $user): int
    {
        return $user instanceof User ? (int) $user->getKey() : (int) $user;
    }

    private function resolveDictionaryId(UserDictionary|int $dictionary): int
    {
        return $dictionary instanceof UserDictionary ? (int) $dictionary->getKey() : (int) $dictionary;
    }

    private function resolveReadyDictionaryId(ReadyDictionary|int $dictionary): int
    {
        return $dictionary instanceof ReadyDictionary ? (int) $dictionary->getKey() : (int) $dictionary;
    }

    private function resolveWordId(Word|int $word): int
    {
        return $word instanceof Word ? (int) $word->getKey() : (int) $word;
    }

    private function resolveReadyDictionaryWordId(ReadyDictionaryWord|int $word): int
    {
        return $word instanceof ReadyDictionaryWord ? (int) $word->getKey() : (int) $word;
    }

    private function assertUserDictionaryContainsWord(int $dictionaryId, int $wordId): void
    {
        $exists = UserDictionary::query()
            ->whereKey($dictionaryId)
            ->whereHas('words', fn (Builder $query): Builder => $query->whereKey($wordId))
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('The provided word does not belong to the provided user dictionary.');
        }
    }

    private function assertReadyDictionaryContainsWord(int $dictionaryId, int $wordId): void
    {
        $exists = ReadyDictionaryWord::query()
            ->whereKey($wordId)
            ->where('ready_dictionary_id', $dictionaryId)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('The provided word does not belong to the provided ready dictionary.');
        }
    }
}
