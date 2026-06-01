<?php

namespace App\Services\Translation;

interface TextTranslationServiceInterface
{
    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string;
}
