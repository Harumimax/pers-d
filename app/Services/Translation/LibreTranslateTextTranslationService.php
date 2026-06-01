<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;

class LibreTranslateTextTranslationService implements TextTranslationServiceInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        $payload = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
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

        return trim((string) Arr::get($response->json(), 'translatedText', ''));
    }
}
