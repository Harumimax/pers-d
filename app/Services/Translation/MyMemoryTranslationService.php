<?php

namespace App\Services\Translation;

use App\Services\Translation\Data\TranslationResult;
use App\Services\Translation\Data\TranslationSuggestion;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;

class MyMemoryTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult
    {
        $response = $this->http
            ->baseUrl((string) config('services.mymemory.base_url'))
            ->timeout((int) config('services.mymemory.timeout', 10))
            ->acceptJson()
            ->get('/get', [
                'q' => $text,
                'langpair' => $sourceLanguage.'|'.$targetLanguage,
                'mt' => (int) config('services.mymemory.mt', true),
            ])
            ->throw();

        return new TranslationResult($this->extractSuggestions($response->json()));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, TranslationSuggestion>
     */
    private function extractSuggestions(array $payload): array
    {
        /** @var array<int, TranslationSuggestion> $suggestions */
        $suggestions = [];
        /** @var array<string, bool> $seen */
        $seen = [];

        $primaryTranslation = trim((string) Arr::get($payload, 'responseData.translatedText', ''));
        if ($primaryTranslation !== '') {
            $seen[mb_strtolower($primaryTranslation)] = true;
            $suggestions[] = new TranslationSuggestion($primaryTranslation, 'top result');
        }

        foreach (Arr::get($payload, 'matches', []) as $match) {
            if (! is_array($match)) {
                continue;
            }

            $translation = trim((string) Arr::get($match, 'translation', ''));
            if ($translation === '') {
                continue;
            }

            $normalized = mb_strtolower($translation);
            if (isset($seen[$normalized])) {
                continue;
            }

            $label = $this->suggestionLabel($match);
            $seen[$normalized] = true;
            $suggestions[] = new TranslationSuggestion($translation, $label);

            if (count($suggestions) >= 6) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * @param array<string, mixed> $match
     */
    private function suggestionLabel(array $match): string
    {
        $matchScore = Arr::get($match, 'match');
        $createdBy = trim((string) Arr::get($match, 'created-by', ''));

        if (is_numeric($matchScore) && (float) $matchScore >= 0.99) {
            return 'best match';
        }

        if ($createdBy !== '') {
            return 'memory match';
        }

        return 'suggested';
    }
}
