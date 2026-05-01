<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewRunItem;
use App\Models\TelegramIntervalReviewSession;
use App\Services\Remainder\Core\RemainderMistakeFlagSyncService;
use Illuminate\Support\Collection;

class TelegramIntervalReviewResultFinalizer
{
    public function __construct(
        private readonly RemainderMistakeFlagSyncService $remainderMistakeFlagSyncService,
    ) {
    }

    /**
     * @return array{
     *   run:TelegramIntervalReviewRun,
     *   correct_answers:int,
     *   incorrect_answers:int,
     *   total_words:int,
     *   incorrect_items:Collection<int, TelegramIntervalReviewRunItem>,
     *   summary_text:string,
     *   plan_completed:bool,
     *   completion_message:?string
     * }
     */
    public function finalize(TelegramIntervalReviewRun $run): array
    {
        $run->unsetRelation('items');
        $run->unsetRelation('plan');
        $run->unsetRelation('session');
        $run->load(['items', 'plan.sessions', 'session', 'user']);

        /** @var Collection<int, TelegramIntervalReviewRunItem> $incorrectItems */
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
            'status' => TelegramIntervalReviewRun::STATUS_FINISHED,
            'finished_at' => $run->finished_at ?? now(),
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $incorrectAnswers,
            'last_interaction_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'last_error_at' => null,
        ])->save();

        $run->session->forceFill([
            'status' => TelegramIntervalReviewSession::STATUS_FINISHED,
        ])->save();

        $this->remainderMistakeFlagSyncService->syncTelegramIntervalReviewRun($run);

        $plan = $run->plan->fresh('sessions');
        $completedSessionsCount = $plan->sessions
            ->where('status', TelegramIntervalReviewSession::STATUS_FINISHED)
            ->count();
        $planCompleted = $completedSessionsCount >= 6;

        $plan->forceFill([
            'completed_sessions_count' => $completedSessionsCount,
            'status' => $planCompleted ? TelegramIntervalReviewPlan::STATUS_COMPLETED : $plan->status,
            'completed_at' => $planCompleted ? ($plan->completed_at ?? now()) : null,
        ])->save();

        /** @var TelegramIntervalReviewRun $freshRun */
        $freshRun = $run->fresh(['items', 'user', 'plan.sessions', 'session']);

        return [
            'run' => $freshRun,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $incorrectAnswers,
            'total_words' => $totalWords,
            'incorrect_items' => $incorrectItems,
            'summary_text' => $this->buildSummaryMessage($correctAnswers, $incorrectAnswers, $totalWords, $incorrectItems),
            'plan_completed' => $planCompleted,
            'completion_message' => $planCompleted ? 'Интервальное повторение выбранных слов завершено.' : null,
        ];
    }

    /**
     * @param Collection<int, TelegramIntervalReviewRunItem> $incorrectItems
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
            "Ошибок: {$incorrectAnswers}.",
        ];

        if ($incorrectAnswers === 0) {
            $lines[] = '';
            $lines[] = 'Ошибок нет. Отличный результат.';

            return implode("\n", $lines);
        }

        $errorBlocks = $incorrectItems
            ->values()
            ->map(function (TelegramIntervalReviewRunItem $item, int $index): string {
                $blockLines = [
                    ($index + 1).'. '.$item->prompt_text,
                    'Правильный ответ: '.$item->correct_answer,
                    'Ваш ответ: '.($item->user_answer ?? '—'),
                ];

                return implode("\n", $blockLines);
            })
            ->all();

        $lines[] = '';
        $lines[] = 'Ошибки:';
        $lines[] = implode("\n\n", $errorBlocks);

        return implode("\n", $lines);
    }
}
