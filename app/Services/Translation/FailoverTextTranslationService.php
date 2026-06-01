<?php

namespace App\Services\Translation;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FailoverTextTranslationService implements TextTranslationServiceInterface
{
    private const LIBRETRANSLATE_PROVIDER = 'libretranslate';
    private const MYMEMORY_PROVIDER = 'mymemory';
    private const UNHEALTHY_CACHE_KEY = 'translation:libretranslate:unhealthy';

    public function __construct(
        private readonly LibreTranslateTextTranslationService $libreTranslateTranslationService,
        private readonly MyMemoryTextTranslationService $myMemoryTranslationService,
        private readonly CacheRepository $cache,
    ) {
    }

    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        $primaryProvider = $this->configuredProvider('services.translation.primary_provider', self::LIBRETRANSLATE_PROVIDER);
        $fallbackProvider = $this->configuredProvider('services.translation.fallback_provider', self::MYMEMORY_PROVIDER);

        if ($primaryProvider === $fallbackProvider) {
            return $this->provider($primaryProvider)->translateText($text, $sourceLanguage, $targetLanguage);
        }

        if ($this->isProviderMarkedUnhealthy($primaryProvider)) {
            Log::warning('text_translation.primary_provider_marked_unhealthy', [
                'primary_provider' => $primaryProvider,
                'fallback_provider' => $fallbackProvider,
            ]);

            return $this->provider($fallbackProvider)->translateText($text, $sourceLanguage, $targetLanguage);
        }

        try {
            return $this->provider($primaryProvider)->translateText($text, $sourceLanguage, $targetLanguage);
        } catch (ConnectionException|RequestException $exception) {
            $this->markProviderUnhealthy($primaryProvider);

            Log::warning('text_translation.primary_provider_failed_fallback_used', [
                'primary_provider' => $primaryProvider,
                'fallback_provider' => $fallbackProvider,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->provider($fallbackProvider)->translateText($text, $sourceLanguage, $targetLanguage);
        }
    }

    private function configuredProvider(string $configKey, string $default): string
    {
        $provider = trim((string) config($configKey, $default));

        return $provider !== '' ? $provider : $default;
    }

    private function isProviderMarkedUnhealthy(string $provider): bool
    {
        if ($provider !== self::LIBRETRANSLATE_PROVIDER) {
            return false;
        }

        return $this->cache->get(self::UNHEALTHY_CACHE_KEY, false) === true;
    }

    private function markProviderUnhealthy(string $provider): void
    {
        if ($provider !== self::LIBRETRANSLATE_PROVIDER) {
            return;
        }

        $ttlMinutes = max(1, (int) config('services.translation.libretranslate_unhealthy_ttl_minutes', 60));

        $this->cache->put(self::UNHEALTHY_CACHE_KEY, true, now()->addMinutes($ttlMinutes));
    }

    private function provider(string $provider): TextTranslationServiceInterface
    {
        return match ($provider) {
            self::LIBRETRANSLATE_PROVIDER => $this->libreTranslateTranslationService,
            self::MYMEMORY_PROVIDER => $this->myMemoryTranslationService,
            default => throw new InvalidArgumentException("Unsupported translation provider [{$provider}]."),
        };
    }
}
