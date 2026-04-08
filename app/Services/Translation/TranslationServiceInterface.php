<?php

namespace App\Services\Translation;

use App\Services\Translation\Data\TranslationResult;

interface TranslationServiceInterface
{
    public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult;
}
