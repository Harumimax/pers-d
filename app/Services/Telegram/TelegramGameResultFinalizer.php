<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Services\Remainder\Core\RemainderMistakeFlagSyncService;
use Illuminate\Support\Collection;

class TelegramGameResultFinalizer
{
    public function __construct(
        private readonly RemainderMistakeFlagSyncService $remainderMistakeFlagSyncService,
    ) {
    }

    /**
     * @return array{
     *   run:TelegramGameRun,
     *   correct_answers:int,
     *   incorrect_answers:int,
     *   total_words:int,
     *   incorrect_items:Collection<int, TelegramGameRunItem>,
     *   summary_text:string
     * }
     */
    public function finalize(TelegramGameRun $run): array
    {
        $run->unsetRelation('items');
        $run->load('items');

        /** @var Collection<int, TelegramGameRunItem> $incorrectItems */
        $incorrectItems = $run->items
            ->where('is_correct', false)
            ->sortBy('order_index')
            ->values();

        $correctAnswers = $run->items
            ->where('is_correct', true)
            ->count();

        $incorrectAnswers = $incorrectItems->count();
        $totalWords = (int) $run->total_words;

        $run->forceFill([
            'status' => TelegramGameRun::STATUS_FINISHED,
            'finished_at' => $run->finished_at ?? now(),
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $incorrectAnswers,
        ])->save();

        $this->remainderMistakeFlagSyncService->syncTelegramRun($run);

        /** @var TelegramGameRun $freshRun */
        $freshRun = $run->fresh(['items', 'user']);

        return [
            'run' => $freshRun,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $incorrectAnswers,
            'total_words' => $totalWords,
            'incorrect_items' => $incorrectItems,
            'summary_text' => $this->buildSummaryMessage($correctAnswers, $incorrectAnswers, $totalWords, $incorrectItems),
        ];
    }

    /**
     * @param Collection<int, TelegramGameRunItem> $incorrectItems
     */
    private function buildSummaryMessage(
        int $correctAnswers,
        int $incorrectAnswers,
        int $totalWords,
        Collection $incorrectItems,
    ): string {
        $lines = [
            'Сессия завершена.',
            "Правильных ответов: {$correctAnswers} из {$totalWords}.",
            "Неправильных ответов: {$incorrectAnswers}.",
        ];

        if ($incorrectAnswers === 0) {
            $lines[] = '';
            $lines[] = 'Ошибок нет. Отличный результат.';

            return implode("\n", $lines);
        }

        $errorBlocks = $incorrectItems
            ->values()
            ->map(function (TelegramGameRunItem $item, int $index): string {
                $lines = [
                    ($index + 1).'. '.$item->prompt_text,
                    'Правильный ответ: '.$item->correct_answer,
                    'Ваш ответ: '.($item->user_answer ?? '—'),
                ];

                return implode("\n", $lines);
            })
            ->all();

        $lines[] = '';
        $lines[] = 'Ошибки:';
        $lines[] = implode("\n\n", $errorBlocks);

        return implode("\n", $lines);
    }
}
