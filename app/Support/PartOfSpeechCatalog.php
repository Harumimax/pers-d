<?php

namespace App\Support;

class PartOfSpeechCatalog
{
    public const ALL = 'all';

    private const VALUES = [
        'noun',
        'verb',
        'adjective',
        'adverb',
        'pronoun',
        'cardinal',
        'preposition',
        'conjunction',
        'interjection',
        'stable_expression',
    ];

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return self::VALUES;
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
        return collect(self::values())
            ->mapWithKeys(static fn (string $value): array => [
                $value => (string) __("parts_of_speech.labels.{$value}"),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function labelsWithAll(): array
    {
        return [
            self::ALL => (string) __('parts_of_speech.labels.all'),
            ...self::labels(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dictionaryFormLabels(): array
    {
        return collect(self::values())
            ->mapWithKeys(static fn (string $value): array => [
                $value => (string) __("parts_of_speech.form_labels.{$value}"),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function dictionaryFilterLabels(): array
    {
        return [
            self::ALL => (string) __('parts_of_speech.filter_labels.all'),
            ...self::dictionaryFormLabels(),
        ];
    }

    public static function label(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::labels()[$value] ?? null;
    }
}
