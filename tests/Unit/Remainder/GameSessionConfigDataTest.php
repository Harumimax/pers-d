<?php

namespace Tests\Unit\Remainder;

use App\Models\GameSession;
use App\Services\Remainder\Core\GameSessionConfigData;
use PHPUnit\Framework\TestCase;

class GameSessionConfigDataTest extends TestCase
{
    public function test_from_array_normalizes_ids_and_parts_of_speech(): void
    {
        $config = GameSessionConfigData::fromArray([
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => ['5', 2, '5'],
            'ready_dictionary_ids' => ['9', '3', 9],
            'parts_of_speech' => [' noun ', 'verb', 'noun'],
            'words_count' => '7',
        ]);

        $this->assertSame(GameSession::MODE_CHOICE, $config->mode);
        $this->assertSame(GameSession::DIRECTION_FOREIGN_TO_RU, $config->direction);
        $this->assertSame([2, 5], $config->dictionaryIds);
        $this->assertSame([3, 9], $config->readyDictionaryIds);
        $this->assertSame(['noun', 'verb'], $config->partsOfSpeech);
        $this->assertSame(7, $config->requestedWordsCount);
        $this->assertTrue($config->usesChoiceMode());
    }

    public function test_from_array_uses_all_part_of_speech_when_empty_or_all_is_present(): void
    {
        $emptyConfig = GameSessionConfigData::fromArray([
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_RU_TO_FOREIGN,
            'dictionary_ids' => [],
            'ready_dictionary_ids' => [],
            'parts_of_speech' => [],
            'words_count' => 3,
        ]);

        $allConfig = GameSessionConfigData::fromArray([
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_RU_TO_FOREIGN,
            'dictionary_ids' => [],
            'ready_dictionary_ids' => [],
            'parts_of_speech' => ['verb', 'all'],
            'words_count' => 3,
        ]);

        $this->assertSame(['all'], $emptyConfig->partsOfSpeech);
        $this->assertSame(['all'], $allConfig->partsOfSpeech);
        $this->assertFalse($emptyConfig->usesChoiceMode());
    }
}
