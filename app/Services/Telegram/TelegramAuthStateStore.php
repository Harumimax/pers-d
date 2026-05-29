<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;

class TelegramAuthStateStore
{
    public const STEP_AWAITING_EMAIL = 'awaiting_email';
    public const STEP_AWAITING_DICTIONARY_SEARCH_QUERY = 'awaiting_dictionary_search_query';
    public const STEP_AWAITING_ADD_WORD_TEXT = 'awaiting_add_word_text';
    public const STEP_AWAITING_ADD_WORD_TRANSLATION = 'awaiting_add_word_translation';
    public const STEP_AWAITING_ADD_WORD_PART_OF_SPEECH = 'awaiting_add_word_part_of_speech';

    private const TTL_MINUTES = 10;

    public function start(string $chatId): void
    {
        $this->put($chatId, [
            'step' => self::STEP_AWAITING_EMAIL,
            'email' => null,
        ] + $this->emptyAddWordState());
    }

    public function startDictionaryWordSearch(string $chatId): void
    {
        $this->put($chatId, [
            'step' => self::STEP_AWAITING_DICTIONARY_SEARCH_QUERY,
            'email' => null,
        ] + $this->emptyAddWordState());
    }

    public function startAddWord(string $chatId, int $dictionaryId, string $dictionaryName, ?string $dictionaryLanguage): void
    {
        $this->put($chatId, [
            'step' => self::STEP_AWAITING_ADD_WORD_TEXT,
            'email' => null,
            'dictionary_id' => $dictionaryId,
            'dictionary_name' => $dictionaryName,
            'dictionary_language' => $dictionaryLanguage,
            'word' => null,
            'translation_options' => [],
            'selected_translation' => null,
        ]);
    }

    /**
     * @param  array<int, array{text:string,label:string}>  $translationOptions
     */
    public function storeAddWordTranslations(string $chatId, string $word, array $translationOptions): void
    {
        $state = $this->get($chatId);

        if ($state === null) {
            return;
        }

        $this->put($chatId, [
            ...$state,
            'step' => self::STEP_AWAITING_ADD_WORD_TRANSLATION,
            'word' => $word,
            'translation_options' => $translationOptions,
            'selected_translation' => null,
        ]);
    }

    public function storeSelectedAddWordTranslation(string $chatId, string $translation): void
    {
        $state = $this->get($chatId);

        if ($state === null) {
            return;
        }

        $this->put($chatId, [
            ...$state,
            'step' => self::STEP_AWAITING_ADD_WORD_PART_OF_SPEECH,
            'selected_translation' => $translation,
        ]);
    }

    /**
     * @return array{
     *     step:string,
     *     email:?string,
     *     dictionary_id?:?int,
     *     dictionary_name?:?string,
     *     dictionary_language?:?string,
     *     word?:?string,
     *     translation_options?:array<int, array{text:string,label:string}>,
     *     selected_translation?:?string
     * }|null
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
     * @param  array<string, mixed>  $state
     */
    private function put(string $chatId, array $state): void
    {
        Cache::put($this->key($chatId), $state, now()->addMinutes(self::TTL_MINUTES));
    }

    /**
     * @return array{
     *     dictionary_id:?int,
     *     dictionary_name:?string,
     *     dictionary_language:?string,
     *     word:?string,
     *     translation_options:array<int, array{text:string,label:string}>,
     *     selected_translation:?string
     * }
     */
    private function emptyAddWordState(): array
    {
        return [
            'dictionary_id' => null,
            'dictionary_name' => null,
            'dictionary_language' => null,
            'word' => null,
            'translation_options' => [],
            'selected_translation' => null,
        ];
    }

    private function key(string $chatId): string
    {
        return 'telegram:auth:'.$chatId;
    }
}
