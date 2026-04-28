<?php

namespace App\Services\Telegram;

use App\Models\GameSession;
use App\Models\TelegramRandomWordSession;
use App\Services\Remainder\Core\GameSessionConfigData;

class TelegramGameConfigFactory
{
    private const DEFAULT_WORDS_COUNT = 10;

    public function fromRandomWordSession(TelegramRandomWordSession $session): GameSessionConfigData
    {
        return new GameSessionConfigData(
            mode: GameSession::MODE_CHOICE,
            direction: (string) $session->translation_direction,
            dictionaryIds: $session->userDictionaries->pluck('id')->map(static fn ($id): int => (int) $id)->sort()->values()->all(),
            readyDictionaryIds: $session->readyDictionaries->pluck('id')->map(static fn ($id): int => (int) $id)->sort()->values()->all(),
            partsOfSpeech: $this->partsOfSpeech($session),
            requestedWordsCount: self::DEFAULT_WORDS_COUNT,
        );
    }

    /**
     * @return array<int, string>
     */
    private function partsOfSpeech(TelegramRandomWordSession $session): array
    {
        $partsOfSpeech = $session->partsOfSpeech
            ->pluck('part_of_speech')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $partsOfSpeech !== [] ? $partsOfSpeech : ['all'];
    }
}
