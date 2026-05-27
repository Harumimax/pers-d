<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;

class TelegramAuthStateStore
{
    public const STEP_AWAITING_EMAIL = 'awaiting_email';
    public const STEP_AWAITING_DICTIONARY_SEARCH_QUERY = 'awaiting_dictionary_search_query';

    private const TTL_MINUTES = 10;

    public function start(string $chatId): void
    {
        $this->put($chatId, [
            'step' => self::STEP_AWAITING_EMAIL,
            'email' => null,
        ]);
    }

    public function startDictionaryWordSearch(string $chatId): void
    {
        $this->put($chatId, [
            'step' => self::STEP_AWAITING_DICTIONARY_SEARCH_QUERY,
            'email' => null,
        ]);
    }

    /**
     * @return array{step:string,email:?string}|null
     */
    public function get(string $chatId): ?array
    {
        $state = Cache::get($this->key($chatId));

        return is_array($state) ? $state : null;
    }

    public function clear(string $chatId): void
    {
        Cache::forget($this->key($chatId));
    }

    /**
     * @param  array{step:string,email:?string}  $state
     */
    private function put(string $chatId, array $state): void
    {
        Cache::put($this->key($chatId), $state, now()->addMinutes(self::TTL_MINUTES));
    }

    private function key(string $chatId): string
    {
        return 'telegram:auth:'.$chatId;
    }
}
