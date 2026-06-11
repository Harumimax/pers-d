<?php

namespace App\Services\Examples;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Support\DictionaryLanguageCode;

class BackfillWordExamplesService
{
    private const CHUNK_SIZE = 50;
    private const TARGET_LANGUAGE = 'ru';
    private const SUPPORTED_DICTIONARY_LANGUAGES = ['English', 'Spanish', 'German', 'Italian', 'Portuguese'];

    public function __construct(
        private readonly ExampleEnrichmentService $exampleEnrichmentService,
    ) {
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    public function backfill(string $source, int $limit = 0, ?int $dictionaryId = null, ?callable $progress = null): array
    {
        return match ($source) {
            'user' => $this->backfillUserWords($limit, $dictionaryId, $progress),
            'ready' => $this->backfillReadyWords($limit, $dictionaryId, $progress),
            default => throw new \InvalidArgumentException("Unsupported backfill source [{$source}]."),
        };
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    public function clear(string $source, int $dictionaryId, ?callable $progress = null): array
    {
        return match ($source) {
            'user' => $this->clearUserWords($dictionaryId, $progress),
            'ready' => $this->clearReadyWords($dictionaryId, $progress),
            default => throw new \InvalidArgumentException("Unsupported clear source [{$source}]."),
        };
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function backfillUserWords(int $limit, ?int $dictionaryId, ?callable $progress): array
    {
        if ($dictionaryId !== null) {
            $dictionary = UserDictionary::query()->find($dictionaryId);

            if ($dictionary === null) {
                throw new \InvalidArgumentException("User dictionary [{$dictionaryId}] was not found.");
            }
        }

        $stats = $this->emptyStats();
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;

        $query = Word::query()
            ->with([
                'dictionaries' => fn ($query) => $query->select('user_dictionaries.id', 'language')->orderBy('user_dictionaries.id'),
                'examples',
            ])
            ->whereHas('dictionaries', function ($query) use ($dictionaryId): void {
                $query->whereIn('language', self::SUPPORTED_DICTIONARY_LANGUAGES);

                if ($dictionaryId !== null) {
                    $query->where('user_dictionaries.id', $dictionaryId);
                }
            })
            ->orderBy('id');

        $query->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, &$remaining, $progress): bool {
                foreach ($words as $word) {
                    if ($remaining <= 0) {
                        return false;
                    }

                    if ($word->examples->isNotEmpty()) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    $sourceLanguage = $word->dictionaries
                        ->map(fn ($dictionary) => DictionaryLanguageCode::fromDictionaryLanguage($dictionary->language))
                        ->first(fn ($language) => $language !== null);

                    if ($sourceLanguage === null) {
                        $stats['skipped_unsupported_language']++;
                        continue;
                    }

                    $stats['processed']++;
                    $remaining--;
                    if ($progress !== null) {
                        $progress('user', (string) $word->word, $stats['processed']);
                    }

                    $storedExamples = $this->exampleEnrichmentService->fetchAndStoreForWord(
                        $word,
                        $sourceLanguage,
                        self::TARGET_LANGUAGE,
                    );

                    if ($storedExamples === []) {
                        $stats['skipped_without_examples']++;
                        continue;
                    }

                    $stats['enriched']++;
                }

                return $remaining > 0;
            });

        return $stats;
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function backfillReadyWords(int $limit, ?int $dictionaryId, ?callable $progress): array
    {
        if ($dictionaryId !== null) {
            $dictionary = ReadyDictionary::query()->find($dictionaryId);

            if ($dictionary === null) {
                throw new \InvalidArgumentException("Ready dictionary [{$dictionaryId}] was not found.");
            }
        }

        $stats = $this->emptyStats();
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;

        $query = ReadyDictionaryWord::query()
            ->with(['readyDictionary:id,language', 'examples'])
            ->whereHas('readyDictionary', function ($query) use ($dictionaryId): void {
                $query->whereIn('language', self::SUPPORTED_DICTIONARY_LANGUAGES);

                if ($dictionaryId !== null) {
                    $query->where('ready_dictionaries.id', $dictionaryId);
                }
            })
            ->orderBy('id');

        $query->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, &$remaining, $progress): bool {
                foreach ($words as $word) {
                    if ($remaining <= 0) {
                        return false;
                    }

                    if ($word->examples->isNotEmpty()) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    $sourceLanguage = DictionaryLanguageCode::fromDictionaryLanguage($word->readyDictionary?->language);

                    if ($sourceLanguage === null) {
                        $stats['skipped_unsupported_language']++;
                        continue;
                    }

                    $stats['processed']++;
                    $remaining--;
                    if ($progress !== null) {
                        $progress('ready', (string) $word->word, $stats['processed']);
                    }

                    $storedExamples = $this->exampleEnrichmentService->fetchAndStoreForWord(
                        $word,
                        $sourceLanguage,
                        self::TARGET_LANGUAGE,
                    );

                    if ($storedExamples === []) {
                        $stats['skipped_without_examples']++;
                        continue;
                    }

                    $stats['enriched']++;
                }

                return $remaining > 0;
            });

        return $stats;
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function clearUserWords(int $dictionaryId, ?callable $progress): array
    {
        $dictionary = UserDictionary::query()->find($dictionaryId);

        if ($dictionary === null) {
            throw new \InvalidArgumentException("User dictionary [{$dictionaryId}] was not found.");
        }

        $stats = $this->emptyStats();

        Word::query()
            ->with([
                'dictionaries' => fn ($query) => $query->select('user_dictionaries.id'),
                'examples',
            ])
            ->whereHas('dictionaries', fn ($query) => $query->where('user_dictionaries.id', $dictionaryId))
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, $progress): void {
                foreach ($words as $word) {
                    $stats['processed']++;

                    if ($progress !== null) {
                        $progress('user', (string) $word->word, $stats['processed']);
                    }

                    if ($word->examples->isEmpty()) {
                        continue;
                    }

                    $stats['cleared'] += $word->examples->count();
                    $word->examples()->delete();
                }
            });

        return $stats;
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     cleared:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function clearReadyWords(int $dictionaryId, ?callable $progress): array
    {
        $dictionary = ReadyDictionary::query()->find($dictionaryId);

        if ($dictionary === null) {
            throw new \InvalidArgumentException("Ready dictionary [{$dictionaryId}] was not found.");
        }

        $stats = $this->emptyStats();

        ReadyDictionaryWord::query()
            ->with(['examples'])
            ->where('ready_dictionary_id', $dictionaryId)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, $progress): void {
                foreach ($words as $word) {
                    $stats['processed']++;

                    if ($progress !== null) {
                        $progress('ready', (string) $word->word, $stats['processed']);
                    }

                    if ($word->examples->isEmpty()) {
                        continue;
                    }

                    $stats['cleared'] += $word->examples->count();
                    $word->examples()->delete();
                }
            });

        return $stats;
    }

    /**
     * @return array{
     *     processed:int,
     *     enriched:int,
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function emptyStats(): array
    {
        return [
            'processed' => 0,
            'enriched' => 0,
            'cleared' => 0,
            'skipped_existing' => 0,
            'skipped_unsupported_language' => 0,
            'skipped_without_examples' => 0,
        ];
    }
}
