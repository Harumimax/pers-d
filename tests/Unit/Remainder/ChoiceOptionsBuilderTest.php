<?php

namespace Tests\Unit\Remainder;

use App\Services\Remainder\ChoiceOptionsBuilder;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ChoiceOptionsBuilderTest extends TestCase
{
    public function test_builder_prefers_same_part_of_speech_distractors_when_pool_allows_it(): void
    {
        $builder = new ChoiceOptionsBuilder();

        $itemPayloads = collect([[
            'correct_answer' => 'яблоко',
            'part_of_speech_snapshot' => 'noun',
        ]]);

        $availableAnswers = collect([
            ['answer' => 'яблоко', 'part_of_speech' => 'noun'],
            ['answer' => 'груша', 'part_of_speech' => 'noun'],
            ['answer' => 'стол', 'part_of_speech' => 'noun'],
            ['answer' => 'дом', 'part_of_speech' => 'noun'],
            ['answer' => 'море', 'part_of_speech' => 'noun'],
            ['answer' => 'река', 'part_of_speech' => 'noun'],
            ['answer' => 'бежать', 'part_of_speech' => 'verb'],
        ]);

        $result = $builder->build($itemPayloads, $availableAnswers);
        $options = collect($result['items']->first()['options_json']);

        $this->assertCount(6, $options);
        $this->assertSame(
            collect(['груша', 'стол', 'дом', 'море', 'река'])->sort()->values()->all(),
            $options->reject(static fn (string $option): bool => $option === 'яблоко')->sort()->values()->all(),
        );
    }

    public function test_builder_falls_back_to_other_parts_of_speech_when_same_part_pool_is_not_enough(): void
    {
        $builder = new ChoiceOptionsBuilder();

        $itemPayloads = collect([[
            'correct_answer' => 'яблоко',
            'part_of_speech_snapshot' => 'noun',
        ]]);

        $availableAnswers = collect([
            ['answer' => 'яблоко', 'part_of_speech' => 'noun'],
            ['answer' => 'груша', 'part_of_speech' => 'noun'],
            ['answer' => 'стол', 'part_of_speech' => 'noun'],
            ['answer' => 'бежать', 'part_of_speech' => 'verb'],
            ['answer' => 'читать', 'part_of_speech' => 'verb'],
            ['answer' => 'зелёный', 'part_of_speech' => 'adjective'],
        ]);

        $result = $builder->build($itemPayloads, $availableAnswers);
        $options = collect($result['items']->first()['options_json']);
        $distractors = $options->reject(static fn (string $option): bool => $option === 'яблоко')->values();

        $this->assertCount(6, $options);
        $this->assertContains('груша', $distractors);
        $this->assertContains('стол', $distractors);
        $this->assertCount(5, $distractors);
    }
}
