<?php

namespace App\Services\Telegram;

class TelegramAddWordCallbackData
{
    public const ACTION_DICTIONARY = 'dictionary';
    public const ACTION_TRANSLATION = 'translation';
    public const ACTION_PART_OF_SPEECH = 'part_of_speech';
    public const ACTION_CANCEL = 'cancel';

    public function makeDictionary(int $dictionaryId): string
    {
        return 'tg_add_word:'.self::ACTION_DICTIONARY.":{$dictionaryId}";
    }

    public function makeTranslation(int $index): string
    {
        return 'tg_add_word:'.self::ACTION_TRANSLATION.":{$index}";
    }

    public function makePartOfSpeech(string $value): string
    {
        return 'tg_add_word:'.self::ACTION_PART_OF_SPEECH.":{$value}";
    }

    public function makeCancel(): string
    {
        return 'tg_add_word:'.self::ACTION_CANCEL.':1';
    }

    /**
     * @return array<string, int|string>|null
     */
    public function parse(string $payload): ?array
    {
        $payload = trim($payload);

        if ($payload === '' || ! str_starts_with($payload, 'tg_add_word:')) {
            return null;
        }

        $parts = explode(':', $payload, 3);
        $action = $parts[1] ?? null;
        $value = $parts[2] ?? null;

        if (! is_string($action) || $action === '' || ! is_string($value) || $value === '') {
            return null;
        }

        if ($action === self::ACTION_CANCEL) {
            return [
                'type' => 'add_word',
                'action' => $action,
                'value' => $value,
            ];
        }

        if ($action === self::ACTION_DICTIONARY || $action === self::ACTION_TRANSLATION) {
            if (! is_numeric($value) || (int) $value <= 0 && $action === self::ACTION_DICTIONARY) {
                return null;
            }

            return [
                'type' => 'add_word',
                'action' => $action,
                'value' => (int) $value,
            ];
        }

        if ($action !== self::ACTION_PART_OF_SPEECH) {
            return null;
        }

        return [
            'type' => 'add_word',
            'action' => $action,
            'value' => $value,
        ];
    }
}
