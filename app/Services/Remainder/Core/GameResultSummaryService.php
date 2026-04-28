<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use Illuminate\Support\Collection;

class GameResultSummaryService
{
    /**
     * @return array{correct_answers:int,total_words:int,incorrect_items:Collection<int, GameSessionItem>}
     */
    public function summarize(GameSession $gameSession): array
    {
        /** @var Collection<int, GameSessionItem> $incorrectItems */
        $incorrectItems = $gameSession->items()
            ->where('is_correct', false)
            ->orderBy('order_index')
            ->get();

        return [
            'correct_answers' => (int) $gameSession->correct_answers,
            'total_words' => (int) $gameSession->total_words,
            'incorrect_items' => $incorrectItems,
        ];
    }
}
