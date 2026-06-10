<?php

namespace App\Services\Examples;

use App\Models\ReadyDictionaryWord;
use App\Models\Word;
use App\Support\DictionaryLanguageCode;

class BackfillWordExamplesService
{
    private const CHUNK_SIZE = 50;
    private const TARGET_LANGUAGE = 'ru';

    public function __construct(
        private readonly ExampleEnrichmentService $exampleEnrichmentService,
    ) {
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
    public function backfill(string $source, int $limit = 0, ?callable $progress = null): array
    {
        return match ($source) {
            'user' => $this->backfillUserWords($limit, $progress),
            'ready' => $this->backfillReadyWords($limit, $progress),
            default => throw new \InvalidArgumentException("Unsupported backfill source [{$source}]."),
        };
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
    private function backfillUserWords(int $limit, ?callable $progress): array
    {
        $stats = $this->emptyStats();
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;

        Word::query()
            ->with([
                'dictionaries' => fn ($query) => $query->select('user_dictionaries.id', 'language')->orderBy('user_dictionaries.id'),
                'examples',
            ])
            ->whereHas('dictionaries', fn ($query) => $query->whereIn('language', ['English', 'Spanish']))
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, &$remaining, $progress): bool {
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
     *     skipped_existing:int,
     *     skipped_unsupported_language:int,
     *     skipped_without_examples:int
     * }
     */
    private function backfillReadyWords(int $limit, ?callable $progress): array
    {
        $stats = $this->emptyStats();
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;

        ReadyDictionaryWord::query()
            ->with(['readyDictionary:id,language', 'examples'])
            ->whereHas('readyDictionary', fn ($query) => $query->whereIn('language', ['English', 'Spanish']))
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($words) use (&$stats, &$remaining, $progress): bool {
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
            'skipped_existing' => 0,
            'skipped_unsupported_language' => 0,
            'skipped_without_examples' => 0,
        ];
    }
}
