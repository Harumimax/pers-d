<?php

namespace App\Services\Translation;

use App\Services\Translation\Data\TranslationResult;
use App\Services\Translation\Data\TranslationSuggestion;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;

class LibreTranslateTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult
    {
        $payload = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
            'alternatives' => (int) config('services.libretranslate.alternatives', 3),
        ];

        $apiKey = trim((string) config('services.libretranslate.api_key', ''));
        if ($apiKey !== '') {
            $payload['api_key'] = $apiKey;
        }

        $response = $this->http
            ->baseUrl((string) config('services.libretranslate.base_url'))
            ->timeout((int) config('services.libretranslate.timeout', 10))
            ->acceptJson()
            ->asJson()
            ->post('/translate', $payload)
            ->throw();

        return new TranslationResult($this->extractSuggestions($response->json()));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, TranslationSuggestion>
     */
    private function extractSuggestions(array $payload): array
    {
        /** @var array<int, TranslationSuggestion> $suggestions */
        $suggestions = [];
        /** @var array<string, bool> $seen */
        $seen = [];

        $primaryTranslation = $this->normalizeSuggestionText(Arr::get($payload, 'translatedText'));
        if ($primaryTranslation !== null) {
            $seen[mb_strtolower($primaryTranslation)] = true;
            $suggestions[] = new TranslationSuggestion($primaryTranslation, 'top result');
        }

        foreach (Arr::wrap(Arr::get($payload, 'alternatives', [])) as $alternative) {
            $alternativeText = $this->normalizeSuggestionText($alternative);

            if ($alternativeText === null) {
                continue;
            }

            $normalized = mb_strtolower($alternativeText);
            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $suggestions[] = new TranslationSuggestion($alternativeText, 'alternative');
        }

        return $suggestions;
    }

    private function normalizeSuggestionText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
