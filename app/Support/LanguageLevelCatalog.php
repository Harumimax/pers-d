<?php

namespace App\Support;

class LanguageLevelCatalog
{
    private const LABELS = [
        'A0' => 'A0 Beginner',
        'A1' => 'A1 Elementary',
        'A2' => 'A2 Pre-intermediate',
        'B1' => 'B1 Intermediate',
        'B2' => 'B2 Upper-Intermediate',
        'C1' => 'C1 Advanced',
        'C2' => 'C2 Proficiency',
    ];

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function label(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::LABELS[$value] ?? null;
    }
}
