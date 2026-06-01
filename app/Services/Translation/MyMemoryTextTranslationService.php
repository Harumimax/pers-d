<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;

class MyMemoryTextTranslationService implements TextTranslationServiceInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
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

        return trim((string) Arr::get($response->json(), 'responseData.translatedText', ''));
    }
}
