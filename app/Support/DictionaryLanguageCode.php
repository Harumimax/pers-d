<?php

namespace App\Support;

class DictionaryLanguageCode
{
    /**
     * @var array<string, string>
     */
    private const MAP = [
        'english' => 'en',
        'spanish' => 'es',
        'german' => 'de',
        'italian' => 'it',
        'portuguese' => 'pt',
    ];

    public static function fromDictionaryLanguage(?string $language): ?string
    {
        if (! is_string($language)) {
            return null;
        }

        $normalized = strtolower(trim($language));

        return self::MAP[$normalized] ?? null;
    }
}
