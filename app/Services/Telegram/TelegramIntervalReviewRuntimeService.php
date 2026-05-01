<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewRunItem;
use App\Models\TelegramIntervalReviewSession;
use App\Services\Remainder\Core\GameAnswerEvaluator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TelegramIntervalReviewRuntimeService
{
    public function __construct(
        private readonly GameAnswerEvaluator $gameAnswerEvaluator,
        private readonly TelegramIntervalReviewWordListSender $wordListSender,
        private readonly TelegramIntervalReviewQuestionSender $questionSender,
        private readonly TelegramIntervalReviewResultFinalizer $telegramIntervalReviewResultFinalizer,
    ) {
    }

    /**
     * @return array{status:string,run:TelegramIntervalReviewRun}
     */
    public function startRun(TelegramIntervalReviewRun $run): array
    {
        return DB::transaction(function () use ($run): array {
            /** @var TelegramIntervalReviewRun $lockedRun */
            $lockedRun = TelegramIntervalReviewRun::query()
                ->with(['session', 'items', 'user'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status === TelegramIntervalReviewRun::STATUS_IN_PROGRESS) {
                return [
                    'status' => 'already_started',
                    'run' => $lockedRun,
                ];
            }

            if ($lockedRun->status === TelegramIntervalReviewRun::STATUS_CANCELLED) {
                return [
                    'status' => 'cancelled',
                    'run' => $lockedRun,
                ];
            }

            if ($lockedRun->status !== TelegramIntervalReviewRun::STATUS_AWAITING_START) {
                return [
                    'status' => 'not_startable',
                    'run' => $lockedRun,
                ];
            }

            if ($lockedRun->items->isEmpty()) {
                $lockedRun->forceFill([
                    'status' => TelegramIntervalReviewRun::STATUS_FINISHED,
                    'started_at' => now(),
                    'finished_at' => now(),
                    'last_interaction_at' => now(),
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'last_error_at' => null,
                ])->save();

                $lockedRun->session->forceFill([
                    'status' => TelegramIntervalReviewSession::STATUS_FINISHED,
                ])->save();

                return [
                    'status' => 'finished_without_items',
                    'run' => $lockedRun->fresh(['session', 'items', 'user']),
                ];
            }

            $lockedRun->forceFill([
                'status' => TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'last_interaction_at' => now(),
                'last_error_code' => null,
                'last_error_message' => null,
                'last_error_at' => null,
            ])->save();

            $lockedRun->session->forceFill([
                'status' => TelegramIntervalReviewSession::STATUS_IN_PROGRESS,
            ])->save();

            return [
                'status' => 'started',
                'run' => $lockedRun->fresh(['session', 'items', 'user']),
            ];
        });
    }

    /**
     * @return array{status:string,run:TelegramIntervalReviewRun,message_id?:int|null}
     */
    public function sendWordList(TelegramIntervalReviewRun $run): array
    {
        $freshRun = $run->fresh(['items', 'user', 'session']);
        $response = $this->wordListSender->send($freshRun);
        $messageId = data_get($response, 'result.message_id');

        if (is_numeric($messageId)) {
            $freshRun->forceFill([
                'word_list_message_id' => (int) $messageId,
                'last_interaction_at' => now(),
            ])->save();
        }

        return [
            'status' => 'sent',
            'run' => $freshRun->fresh(['items', 'user', 'session']),
            'message_id' => is_numeric($messageId) ? (int) $messageId : null,
        ];
    }

    /**
     * @return array{status:string,run:TelegramIntervalReviewRun,next_item?:TelegramIntervalReviewRunItem|null,summary_text?:string|null}
     */
    public function beginQuiz(TelegramIntervalReviewRun $run): array
    {
        return DB::transaction(function () use ($run): array {
            /** @var TelegramIntervalReviewRun $lockedRun */
            $lockedRun = TelegramIntervalReviewRun::query()
                ->with(['session', 'items', 'user'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status !== TelegramIntervalReviewRun::STATUS_IN_PROGRESS) {
                return [
                    'status' => 'run_not_in_progress',
                    'run' => $lockedRun,
                ];
            }

            if ($lockedRun->word_list_message_id === null) {
                return [
                    'status' => 'quiz_already_started',
                    'run' => $lockedRun,
                ];
            }

            $nextItem = $this->nextUnansweredItem($lockedRun);

            $lockedRun->forceFill([
                'word_list_message_id' => null,
                'last_interaction_at' => now(),
            ])->save();

            if (! $nextItem instanceof TelegramIntervalReviewRunItem) {
                $finishedRun = $this->finishRun($lockedRun);

                return [
                    'status' => 'finished_without_questions',
                    'run' => $finishedRun,
                    'summary_text' => 'Сессия завершена.',
                ];
            }

            return [
                'status' => 'quiz_started',
                'run' => $lockedRun->fresh(['session', 'items', 'user']),
                'next_item' => $nextItem,
            ];
        });
    }

    public function sendQuestion(TelegramIntervalReviewRun $run, TelegramIntervalReviewRunItem $item): array
    {
        return $this->questionSender->send($run->fresh('user'), $item);
    }

    /**
     * @return array{
     *     status:string,
     *     run:TelegramIntervalReviewRun,
     *     item?:TelegramIntervalReviewRunItem|null,
     *     is_correct?:bool,
     *     correct_answer?:string,
     *     next_item?:TelegramIntervalReviewRunItem|null,
     *     summary_text?:string|null,
     *     completion_message?:string|null,
     *     plan_completed?:bool,
     *     incorrect_items?:Collection<int, TelegramIntervalReviewRunItem>
     * }
     */
    public function submitAnswer(TelegramIntervalReviewRun $run, int $itemId, int $optionIndex): array
    {
        return DB::transaction(function () use ($run, $itemId, $optionIndex): array {
            /** @var TelegramIntervalReviewRun $lockedRun */
            $lockedRun = TelegramIntervalReviewRun::query()
                ->with(['session', 'items', 'user', 'plan.sessions'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status !== TelegramIntervalReviewRun::STATUS_IN_PROGRESS) {
                return [
                    'status' => 'run_not_in_progress',
                    'run' => $lockedRun,
                ];
            }

            /** @var TelegramIntervalReviewRunItem|null $item */
            $item = $lockedRun->items->firstWhere('id', $itemId);

            if (! $item instanceof TelegramIntervalReviewRunItem) {
                return [
                    'status' => 'item_not_found',
                    'run' => $lockedRun,
                ];
            }

            if ($item->answered_at !== null) {
                return [
                    'status' => 'already_answered',
                    'run' => $lockedRun,
                    'item' => $item,
                ];
            }

            $currentItem = $this->nextUnansweredItem($lockedRun);

            if (! $currentItem instanceof TelegramIntervalReviewRunItem || $currentItem->id !== $item->id) {
                return [
                    'status' => 'wrong_item',
                    'run' => $lockedRun,
                    'item' => $item,
                ];
            }

            $options = collect($item->options_json ?? [])
                ->map(static fn ($option): string => (string) $option)
                ->values();

            if (! $options->has($optionIndex)) {
                return [
                    'status' => 'invalid_option',
                    'run' => $lockedRun,
                    'item' => $item,
                ];
            }

            try {
                [$selectedAnswer, $isCorrect] = $this->gameAnswerEvaluator->evaluateChoiceAnswer(
                    (string) $options[$optionIndex],
                    $options->all(),
                    $item->correct_answer,
                );
            } catch (ValidationException) {
                return [
                    'status' => 'invalid_option',
                    'run' => $lockedRun,
                    'item' => $item,
                ];
            }

            $item->forceFill([
                'user_answer' => $selectedAnswer,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ])->save();

            $lockedRun->forceFill([
                'last_interaction_at' => now(),
            ])->save();

            $nextItem = $this->nextUnansweredItem($lockedRun->fresh(['items', 'session', 'user', 'plan.sessions']));

            if ($nextItem instanceof TelegramIntervalReviewRunItem) {
                return [
                    'status' => 'answered',
                    'run' => $lockedRun->fresh(['session', 'items', 'user', 'plan.sessions']),
                    'item' => $item->fresh(),
                    'is_correct' => $isCorrect,
                    'correct_answer' => $item->correct_answer,
                    'next_item' => $nextItem,
                ];
            }

            $finalized = $this->telegramIntervalReviewResultFinalizer->finalize($lockedRun);

            return [
                'status' => 'finished',
                'run' => $finalized['run'],
                'item' => $item->fresh(),
                'is_correct' => $isCorrect,
                'correct_answer' => $item->correct_answer,
                'next_item' => null,
                'summary_text' => $finalized['summary_text'],
                'completion_message' => $finalized['completion_message'],
                'plan_completed' => $finalized['plan_completed'],
                'incorrect_items' => $finalized['incorrect_items'],
            ];
        });
    }

    /**
     * @return array{status:string,run:TelegramIntervalReviewRun}
     */
    public function cancelRun(TelegramIntervalReviewRun $run): array
    {
        return DB::transaction(function () use ($run): array {
            /** @var TelegramIntervalReviewRun $lockedRun */
            $lockedRun = TelegramIntervalReviewRun::query()
                ->with(['session'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status === TelegramIntervalReviewRun::STATUS_CANCELLED) {
                return [
                    'status' => 'already_cancelled',
                    'run' => $lockedRun,
                ];
            }

            if ($lockedRun->status !== TelegramIntervalReviewRun::STATUS_AWAITING_START) {
                return [
                    'status' => 'not_cancellable',
                    'run' => $lockedRun,
                ];
            }

            $lockedRun->forceFill([
                'status' => TelegramIntervalReviewRun::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'last_interaction_at' => now(),
                'last_error_code' => null,
                'last_error_message' => null,
                'last_error_at' => null,
            ])->save();

            $lockedRun->session->forceFill([
                'status' => TelegramIntervalReviewSession::STATUS_CANCELLED,
            ])->save();

            return [
                'status' => 'cancelled',
                'run' => $lockedRun->fresh(['session']),
            ];
        });
    }

    private function nextUnansweredItem(TelegramIntervalReviewRun $run): ?TelegramIntervalReviewRunItem
    {
        /** @var TelegramIntervalReviewRunItem|null $item */
        $item = $run->items
            ->first(static fn (TelegramIntervalReviewRunItem $item): bool => $item->answered_at === null);

        return $item;
    }

    private function finishRun(TelegramIntervalReviewRun $run): TelegramIntervalReviewRun
    {
        $finishedRun = $run->fresh(['session', 'items', 'user']);

        $finishedRun->forceFill([
            'status' => TelegramIntervalReviewRun::STATUS_FINISHED,
            'finished_at' => now(),
            'last_interaction_at' => now(),
        ])->save();

        $finishedRun->session->forceFill([
            'status' => TelegramIntervalReviewSession::STATUS_FINISHED,
        ])->save();

        return $finishedRun->fresh(['session', 'items', 'user']);
    }
}
