<?php

namespace App\Services\Telegram;

class TelegramDictionaryCallbackData
{
    public const ACTION_LIST = 'list';
    public const ACTION_SHOW = 'show';
    public const ACTION_PAGE = 'page';
    public const ACTION_BACK = 'back';
    public const ACTION_NOOP = 'noop';

    public function makeList(): string
    {
        return 'tg_dict:'.self::ACTION_LIST;
    }

    public function makeBack(): string
    {
        return 'tg_dict:'.self::ACTION_BACK;
    }

    public function makeNoop(): string
    {
        return 'tg_dict:'.self::ACTION_NOOP;
    }

    public function makeShow(int $dictionaryId, int $page = 1): string
    {
        return "tg_dict:".self::ACTION_SHOW.":{$dictionaryId}:{$page}";
    }

    public function makePage(int $dictionaryId, int $page): string
    {
        return "tg_dict:".self::ACTION_PAGE.":{$dictionaryId}:{$page}";
    }

    /**
     * @return array<string, int|string>|null
     */
    public function parse(string $payload): ?array
    {
        $payload = trim($payload);

        if ($payload === '' || ! str_starts_with($payload, 'tg_dict:')) {
            return null;
        }

        $parts = explode(':', $payload);
        $action = $parts[1] ?? null;

        if (! is_string($action) || $action === '') {
            return null;
        }

        if (in_array($action, [self::ACTION_LIST, self::ACTION_BACK, self::ACTION_NOOP], true)) {
            return [
                'type' => 'dictionary',
                'action' => $action,
            ];
        }

        if (! in_array($action, [self::ACTION_SHOW, self::ACTION_PAGE], true)) {
            return null;
        }

        $dictionaryId = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;
        $page = isset($parts[3]) && is_numeric($parts[3]) ? (int) $parts[3] : null;

        if ($dictionaryId === null || $dictionaryId <= 0 || $page === null || $page <= 0) {
            return null;
        }

        return [
            'type' => 'dictionary',
            'action' => $action,
            'dictionary_id' => $dictionaryId,
            'page' => $page,
        ];
    }
}
