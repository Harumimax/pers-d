<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Services\Remainder\Core\GameAnswerEvaluator;
use Illuminate\Support\Facades\DB;

class TelegramGameRuntimeService
{
    public function __construct(
        private readonly GameAnswerEvaluator $gameAnswerEvaluator,
        private readonly TelegramGameQuestionSender $telegramGameQuestionSender,
    ) {
    }

    /**
     * @return array{status:string,first_item:?TelegramGameRunItem}
     */
    public function startRun(TelegramGameRun $run): array
    {
        return DB::transaction(function () use ($run): array {
            /** @var TelegramGameRun $lockedRun */
            $lockedRun = TelegramGameRun::query()
                ->with(['user', 'items'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status === TelegramGameRun::STATUS_IN_PROGRESS) {
                return [
                    'status' => 'already_started',
                    'first_item' => $this->nextUnansweredItem($lockedRun),
                ];
            }

            if ($lockedRun->status === TelegramGameRun::STATUS_CANCELLED) {
                return [
                    'status' => 'cancelled',
                    'first_item' => null,
                ];
            }

            if ($lockedRun->status !== TelegramGameRun::STATUS_AWAITING_START) {
                return [
                    'status' => 'not_startable',
                    'first_item' => null,
                ];
            }

            $firstItem = $this->nextUnansweredItem($lockedRun);

            if (! $firstItem instanceof TelegramGameRunItem) {
                $lockedRun->forceFill([
                    'status' => TelegramGameRun::STATUS_FINISHED,
                    'started_at' => now(),
                    'finished_at' => now(),
                ])->save();

                return [
                    'status' => 'finished_without_items',
                    'first_item' => null,
                ];
            }

            $lockedRun->forceFill([
                'status' => TelegramGameRun::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ])->save();

            return [
                'status' => 'started',
                'first_item' => $firstItem,
            ];
        });
    }

    /**
     * @return array{
     *   status:string,
     *   run:TelegramGameRun,
     *   item:?TelegramGameRunItem,
     *   selected_answer:?string,
     *   is_correct:?bool,
     *   correct_answer:?string,
     *   next_item:?TelegramGameRunItem
     * }
     */
    public function submitAnswer(TelegramGameRun $run, int $itemId, int $optionIndex): array
    {
        return DB::transaction(function () use ($run, $itemId, $optionIndex): array {
            /** @var TelegramGameRun $lockedRun */
            $lockedRun = TelegramGameRun::query()
                ->with(['user', 'items'])
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status !== TelegramGameRun::STATUS_IN_PROGRESS) {
                return [
                    'status' => 'run_not_in_progress',
                    'run' => $lockedRun,
                    'item' => null,
                    'selected_answer' => null,
                    'is_correct' => null,
                    'correct_answer' => null,
                    'next_item' => null,
                ];
            }

            /** @var TelegramGameRunItem|null $currentItem */
            $currentItem = $lockedRun->items->firstWhere('id', $itemId);

            if (! $currentItem instanceof TelegramGameRunItem) {
                return [
                    'status' => 'item_not_found',
                    'run' => $lockedRun,
                    'item' => null,
                    'selected_answer' => null,
                    'is_correct' => null,
                    'correct_answer' => null,
                    'next_item' => null,
                ];
            }

            if ($currentItem->answered_at !== null) {
                return [
                    'status' => 'already_answered',
                    'run' => $lockedRun,
                    'item' => $currentItem,
                    'selected_answer' => null,
                    'is_correct' => $currentItem->is_correct,
                    'correct_answer' => $currentItem->correct_answer,
                    'next_item' => $this->nextUnansweredItem($lockedRun),
                ];
            }

            $expectedItem = $this->nextUnansweredItem($lockedRun);

            if (! $expectedItem instanceof TelegramGameRunItem || $expectedItem->id !== $currentItem->id) {
                return [
                    'status' => 'wrong_item',
                    'run' => $lockedRun,
                    'item' => $expectedItem,
                    'selected_answer' => null,
                    'is_correct' => null,
                    'correct_answer' => null,
                    'next_item' => $expectedItem,
                ];
            }

            $options = collect($currentItem->options_json ?? [])
                ->map(static fn ($option): string => (string) $option)
                ->values();

            $selectedAnswer = $options->get($optionIndex);

            if (! is_string($selectedAnswer)) {
                return [
                    'status' => 'invalid_option',
                    'run' => $lockedRun,
                    'item' => $currentItem,
                    'selected_answer' => null,
                    'is_correct' => null,
                    'correct_answer' => $currentItem->correct_answer,
                    'next_item' => $currentItem,
                ];
            }

            [$sanitizedAnswer, $isCorrect] = $this->gameAnswerEvaluator->evaluateChoiceAnswer(
                $selectedAnswer,
                $options->all(),
                $currentItem->correct_answer,
            );

            $currentItem->forceFill([
                'user_answer' => $sanitizedAnswer,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ])->save();

            $lockedRun->unsetRelation('items');
            $lockedRun->load('items');

            $nextItem = $this->nextUnansweredItem($lockedRun);

            if (! $nextItem instanceof TelegramGameRunItem) {
                $lockedRun->forceFill([
                    'status' => TelegramGameRun::STATUS_FINISHED,
                    'finished_at' => now(),
                ])->save();
            }

            return [
                'status' => 'answered',
                'run' => $lockedRun->fresh(['user', 'items']),
                'item' => $currentItem->fresh(),
                'selected_answer' => $sanitizedAnswer,
                'is_correct' => $isCorrect,
                'correct_answer' => $currentItem->correct_answer,
                'next_item' => $nextItem?->fresh(),
            ];
        });
    }

    public function sendQuestion(TelegramGameRun $run, TelegramGameRunItem $item): array
    {
        return $this->telegramGameQuestionSender->send($run, $item);
    }

    private function nextUnansweredItem(TelegramGameRun $run): ?TelegramGameRunItem
    {
        /** @var TelegramGameRunItem|null $item */
        $item = $run->items
            ->sortBy('order_index')
            ->first(fn (TelegramGameRunItem $candidate): bool => $candidate->answered_at === null);

        return $item;
    }
}
