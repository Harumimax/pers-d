<?php

namespace App\Services\Examples;

use App\Services\Examples\Data\WordExampleData;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class TatoebaExampleProvider implements ExampleProviderInterface
{
    private const SOURCE_NAME = 'tatoeba';

    /**
     * @var array<string, string>
     */
    private const LANGUAGE_CODES = [
        'en' => 'eng',
        'es' => 'spa',
        'ru' => 'rus',
    ];

    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function fetchExamples(
        string $word,
        string $sourceLanguage,
        string $targetLanguage,
        int $limit = 3,
    ): array {
        $sourceCode = $this->mapLanguageCode($sourceLanguage);
        $targetCode = $this->mapLanguageCode($targetLanguage);
        $normalizedWord = trim($word);
        $normalizedLimit = max(1, min($limit, (int) config('services.tatoeba.examples_per_word', 3)));

        if ($sourceCode === null || $targetCode === null || $normalizedWord === '') {
            return [];
        }

        $this->throttle();

        $response = $this->http
            ->baseUrl((string) config('services.tatoeba.base_url', 'https://api.tatoeba.org'))
            ->timeout((int) config('services.tatoeba.timeout', 10))
            ->acceptJson()
            ->get('/v1/sentences', [
                'lang' => $sourceCode,
                'q' => $normalizedWord,
                'trans:lang' => $targetCode,
                'showtrans:lang' => $targetCode,
                'showtrans:is_direct' => 'yes',
                'sort' => 'relevance',
                'limit' => max($normalizedLimit * 3, 10),
            ])
            ->throw();

        return $this->extractExamples($response->json(), $normalizedLimit);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, WordExampleData>
     */
    private function extractExamples(array $payload, int $limit): array
    {
        /** @var array<int, WordExampleData> $examples */
        $examples = [];
        /** @var array<string, bool> $seen */
        $seen = [];

        foreach (Arr::wrap(Arr::get($payload, 'data', [])) as $sentence) {
            $exampleText = $this->normalizeText(Arr::get($sentence, 'text'));
            $translation = $this->firstTranslationText($sentence);

            if ($exampleText === null) {
                continue;
            }

            $fingerprint = mb_strtolower($exampleText.'|'.($translation ?? ''));
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $examples[] = new WordExampleData(
                $exampleText,
                $translation,
                self::SOURCE_NAME,
                $this->externalId($sentence),
            );

            if (count($examples) >= $limit) {
                break;
            }
        }

        return $examples;
    }

    /**
     * @param  array<string, mixed>  $sentence
     */
    private function firstTranslationText(array $sentence): ?string
    {
        foreach (Arr::wrap(Arr::get($sentence, 'translations', [])) as $translationGroup) {
            foreach (Arr::wrap($translationGroup) as $translation) {
                $text = $this->normalizeText(Arr::get($translation, 'text'));

                if ($text !== null) {
                    return $text;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $sentence
     */
    private function externalId(array $sentence): ?string
    {
        $id = Arr::get($sentence, 'id');

        if (is_int($id) || is_string($id)) {
            return (string) $id;
        }

        return null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function mapLanguageCode(string $language): ?string
    {
        return self::LANGUAGE_CODES[strtolower(trim($language))] ?? null;
    }

    private function throttle(): void
    {
        $requestsPerSecond = max(0, (int) config('services.tatoeba.requests_per_second', 1));

        if ($requestsPerSecond === 0) {
            return;
        }

        $intervalSeconds = 1 / $requestsPerSecond;

        Cache::lock('tatoeba-example-provider-throttle', 5)->block(5, function () use ($intervalSeconds): void {
            $cacheKey = 'tatoeba-example-provider-last-request-at';
            $lastRequestAt = (float) Cache::get($cacheKey, 0.0);
            $elapsedSeconds = microtime(true) - $lastRequestAt;

            if ($elapsedSeconds < $intervalSeconds) {
                usleep((int) ceil(($intervalSeconds - $elapsedSeconds) * 1_000_000));
            }

            Cache::put($cacheKey, microtime(true), now()->addMinutes(5));
        });
    }
}
