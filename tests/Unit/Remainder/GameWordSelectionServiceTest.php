<?php

namespace Tests\Unit\Remainder;

use App\Services\Remainder\Core\GameWordSelectionService;
use PHPUnit\Framework\TestCase;

class GameWordSelectionServiceTest extends TestCase
{
    public function test_select_words_prioritizes_up_to_fifty_percent_mistake_words(): void
    {
        $service = new GameWordSelectionService();
        $availableWords = collect([
            ['word' => 'mistake-1', 'remainder_had_mistake' => true],
            ['word' => 'mistake-2', 'remainder_had_mistake' => true],
            ['word' => 'clean-1', 'remainder_had_mistake' => false],
            ['word' => 'clean-2', 'remainder_had_mistake' => false],
            ['word' => 'clean-3', 'remainder_had_mistake' => false],
            ['word' => 'clean-4', 'remainder_had_mistake' => false],
        ])->map(fn (array $word): array => array_merge([
            'source' => 'user',
            'word_id' => null,
            'translation' => 'x',
            'part_of_speech' => 'noun',
            'comment' => null,
        ], $word));

        $selectedWords = $service->selectWordsForSession($availableWords, 4);

        $this->assertCount(4, $selectedWords);
        $this->assertSame(2, $selectedWords->where('remainder_had_mistake', true)->count());
    }

    public function test_select_words_tops_up_with_additional_mistake_words_when_clean_words_are_not_enough(): void
    {
        $service = new GameWordSelectionService();
        $availableWords = collect([
            ['word' => 'mistake-1', 'remainder_had_mistake' => true],
            ['word' => 'mistake-2', 'remainder_had_mistake' => true],
            ['word' => 'mistake-3', 'remainder_had_mistake' => true],
            ['word' => 'clean-1', 'remainder_had_mistake' => false],
        ])->map(fn (array $word): array => array_merge([
            'source' => 'user',
            'word_id' => null,
            'translation' => 'x',
            'part_of_speech' => 'noun',
            'comment' => null,
        ], $word));

        $selectedWords = $service->selectWordsForSession($availableWords, 4);

        $this->assertCount(4, $selectedWords);
        $this->assertSame(3, $selectedWords->where('remainder_had_mistake', true)->count());
    }
}
