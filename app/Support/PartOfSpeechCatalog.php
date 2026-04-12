<?php

namespace App\Support;

class PartOfSpeechCatalog
{
    public const ALL = 'all';

    private const LABELS = [
        'noun' => 'Noun',
        'verb' => 'Verb',
        'adjective' => 'Adjective',
        'adverb' => 'Adverb',
        'pronoun' => 'Pronoun',
        'cardinal' => 'Cardinal',
        'preposition' => 'Preposition',
        'conjunction' => 'Conjunction',
        'interjection' => 'Interjection',
        'stable_expression' => 'Stable expression',
    ];

    private const DICTIONARY_FORM_LABELS = [
        'noun' => 'Noun (&#1057;&#1091;&#1097;&#1077;&#1089;&#1090;&#1074;&#1080;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077;)',
        'verb' => 'Verb (&#1043;&#1083;&#1072;&#1075;&#1086;&#1083;)',
        'adjective' => 'Adjective (&#1055;&#1088;&#1080;&#1083;&#1072;&#1075;&#1072;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077;)',
        'adverb' => 'Adverb (&#1053;&#1072;&#1088;&#1077;&#1095;&#1080;&#1077;)',
        'pronoun' => 'Pronoun (&#1052;&#1077;&#1089;&#1090;&#1086;&#1080;&#1084;&#1077;&#1085;&#1080;&#1077;)',
        'cardinal' => 'Cardinal (&#1063;&#1080;&#1089;&#1083;&#1080;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077;)',
        'preposition' => 'Preposition (&#1055;&#1088;&#1077;&#1076;&#1083;&#1086;&#1075;)',
        'conjunction' => 'Conjunction (&#1057;&#1086;&#1102;&#1079;)',
        'interjection' => 'Interjection (&#1052;&#1077;&#1078;&#1076;&#1086;&#1084;&#1077;&#1090;&#1080;&#1077;)',
        'stable_expression' => 'Stable expression (&#1059;&#1089;&#1090;&#1086;&#1081;&#1095;&#1080;&#1074;&#1086;&#1077; &#1074;&#1099;&#1088;&#1072;&#1078;&#1077;&#1085;&#1080;&#1077;)',
    ];

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * @return array<int, string>
     */
    public static function valuesWithAll(): array
    {
        return [
            self::ALL,
            ...self::values(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return self::LABELS;
    }

    /**
     * @return array<string, string>
     */
    public static function labelsWithAll(): array
    {
        return [
            self::ALL => 'All',
            ...self::labels(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dictionaryFormLabels(): array
    {
        return self::DICTIONARY_FORM_LABELS;
    }

    /**
     * @return array<string, string>
     */
    public static function dictionaryFilterLabels(): array
    {
        return [
            self::ALL => 'All (&#1042;&#1089;&#1077;)',
            ...self::dictionaryFormLabels(),
        ];
    }

    public static function label(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::LABELS[$value] ?? null;
    }
}
