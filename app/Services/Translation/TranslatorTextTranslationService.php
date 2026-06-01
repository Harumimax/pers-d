<?php

namespace App\Services\Translation;

class TranslatorTextTranslationService implements TranslatorTextTranslationServiceInterface
{
    public function __construct(
        private readonly LibreTranslateTextTranslationService $libreTranslateTranslationService,
    ) {
    }

    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        return $this->libreTranslateTranslationService->translateText($text, $sourceLanguage, $targetLanguage);
    }
}
