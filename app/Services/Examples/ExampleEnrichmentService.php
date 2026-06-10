<?php

namespace App\Services\Examples;

use App\Models\ReadyDictionaryWord;
use App\Models\Word;
use App\Models\WordExample;
use App\Services\Examples\Data\WordExampleData;
use App\Services\Translation\TextTranslationServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ExampleEnrichmentService
{
    public function __construct(
        private readonly ExampleProviderInterface $provider,
        private readonly TextTranslationServiceInterface $textTranslationService,
    ) {
    }

    /**
     * @param  Word|ReadyDictionaryWord  $word
     * @return array<int, WordExample>
     */
    public function fetchAndStoreForWord(
        Model $word,
        string $sourceLanguage,
        string $targetLanguage,
        ?int $limit = null,
    ): array {
        $examples = $this->fetchExamples(
            (string) $word->getAttribute('word'),
            $sourceLanguage,
            $targetLanguage,
            $limit,
        );

        return $this->replaceExamples($word, $examples);
    }

    /**
     * @return array<int, WordExampleData>
     */
    public function fetchExamples(
        string $word,
        string $sourceLanguage,
        string $targetLanguage,
        ?int $limit = null,
    ): array {
        $normalizedLimit = $limit ?? (int) config('services.tatoeba.examples_per_word', 3);

        try {
            return $this->fillMissingTranslations(
                $this->provider->fetchExamples($word, $sourceLanguage, $targetLanguage, $normalizedLimit),
                $sourceLanguage,
                $targetLanguage,
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to fetch word examples from external provider.', [
                'word' => $word,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'provider' => get_class($this->provider),
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param  Word|ReadyDictionaryWord  $sourceWord
     * @param  Word|ReadyDictionaryWord  $targetWord
     * @return array<int, WordExample>
     */
    public function copyStoredExamples(Model $sourceWord, Model $targetWord): array
    {
        if (! method_exists($sourceWord, 'examples')) {
            return [];
        }

        $copiedExamples = [];

        foreach ($sourceWord->examples()->orderBy('sort_order')->orderBy('id')->get() as $example) {
            $copiedExamples[] = new WordExampleData(
                (string) $example->example_text,
                $example->example_translation !== null ? (string) $example->example_translation : null,
                (string) $example->source,
                $example->source_external_id !== null ? (string) $example->source_external_id : null,
            );
        }

        return $this->replaceExamples($targetWord, $copiedExamples);
    }

    /**
     * @param  Word|ReadyDictionaryWord  $word
     * @param  array<int, WordExampleData>  $examples
     * @return array<int, WordExample>
     */
    public function replaceExamples(Model $word, array $examples): array
    {
        if (! method_exists($word, 'examples')) {
            return [];
        }

        $word->examples()->delete();

        $stored = [];

        foreach (array_values($examples) as $index => $example) {
            $stored[] = $word->examples()->create([
                ...$example->toPersistenceArray(),
                'sort_order' => $index,
            ]);
        }

        return $stored;
    }

    /**
     * @param  array<int, WordExampleData>  $examples
     * @return array<int, WordExampleData>
     */
    private function fillMissingTranslations(array $examples, string $sourceLanguage, string $targetLanguage): array
    {
        $normalizedExamples = [];

        foreach ($examples as $example) {
            if ($example->exampleTranslation !== null && trim($example->exampleTranslation) !== '') {
                $normalizedExamples[] = $example;

                continue;
            }

            try {
                $translatedText = trim($this->textTranslationService->translateText(
                    $example->exampleText,
                    $sourceLanguage,
                    $targetLanguage,
                ));
            } catch (\Throwable $exception) {
                Log::warning('Failed to translate example sentence through fallback translation service.', [
                    'example_text' => $example->exampleText,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($translatedText === '') {
                continue;
            }

            $normalizedExamples[] = $example->withTranslation($translatedText);
        }

        return $normalizedExamples;
    }
}
