<?php

namespace Tests\Unit\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Services\Remainder\Core\GameAnswerEvaluator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GameAnswerEvaluatorTest extends TestCase
{
    public function test_manual_answer_matches_one_translation_option_case_insensitively(): void
    {
        $evaluator = new GameAnswerEvaluator();
        $gameSession = new GameSession(['mode' => GameSession::MODE_MANUAL]);
        $item = new GameSessionItem([
            'correct_answer' => 'can, able to',
        ]);

        [$storedAnswer, $isCorrect] = $evaluator->evaluate($gameSession, $item, '  ABLE TO ');

        $this->assertSame('ABLE TO', $storedAnswer);
        $this->assertTrue($isCorrect);
    }

    public function test_choice_answer_must_be_one_of_available_options(): void
    {
        $this->expectException(ValidationException::class);

        $evaluator = new GameAnswerEvaluator();
        $gameSession = new GameSession(['mode' => GameSession::MODE_CHOICE]);
        $item = new GameSessionItem([
            'correct_answer' => 'red',
            'options_json' => ['red', 'blue', 'green'],
        ]);

        $evaluator->evaluate($gameSession, $item, 'orange');
    }
}
