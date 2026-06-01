<?php

namespace App\Services\Translation;

interface TranslatorTextTranslationServiceInterface
{
    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string;
}
