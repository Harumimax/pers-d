<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Services\Remainder\Core\GameAnswerEvaluator;
use App\Services\Remainder\Core\GameResultSummaryService;
use App\Services\Remainder\Core\RemainderMistakeFlagSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameEngineService
{
    public function __construct(
        private readonly GameAnswerEvaluator $gameAnswerEvaluator,
        private readonly RemainderMistakeFlagSyncService $remainderMistakeFlagSyncService,
        private readonly GameResultSummaryService $gameResultSummaryService,
    ) {
    }

    public function currentItem(GameSession $gameSession): ?GameSessionItem
    {
        return $gameSession->items()
            ->whereNull('answered_at')
            ->orderBy('order_index')
            ->first();
    }

    /**
     * @return array{item: GameSessionItem, finished: bool}
     */
    public function submitAnswer(GameSession $gameSession, string $answer): array
    {
        /** @var array{item: GameSessionItem, finished: bool} $result */
        $result = DB::transaction(function () use ($gameSession, $answer): array {
            /** @var GameSession $session */
            $session = GameSession::query()
                ->whereKey($gameSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== GameSession::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'answer' => __('remainder.messages.play.finished'),
                ]);
            }

            /** @var GameSessionItem|null $currentItem */
            $currentItem = GameSessionItem::query()
                ->where('game_session_id', $session->id)
                ->whereNull('answered_at')
                ->orderBy('order_index')
                ->lockForUpdate()
                ->first();

            if ($currentItem === null) {
                $session->forceFill([
                    'status' => GameSession::STATUS_FINISHED,
                    'finished_at' => now(),
                ])->save();
                $this->remainderMistakeFlagSyncService->sync($session);

                throw ValidationException::withMessages([
                    'answer' => __('remainder.messages.play.finished'),
                ]);
            }

            [$storedAnswer, $isCorrect] = $this->gameAnswerEvaluator->evaluate($session, $currentItem, $answer);

            $currentItem->forceFill([
                'user_answer' => $storedAnswer,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ])->save();

            if ($isCorrect) {
                $session->increment('correct_answers');
                $session->refresh();
            }

            $hasRemainingItems = GameSessionItem::query()
                ->where('game_session_id', $session->id)
                ->whereNull('answered_at')
                ->exists();

            if (! $hasRemainingItems) {
                $session->forceFill([
                    'status' => GameSession::STATUS_FINISHED,
                    'finished_at' => now(),
                ])->save();
                $this->remainderMistakeFlagSyncService->sync($session);
            }

            return [
                'item' => $currentItem->fresh(),
                'finished' => ! $hasRemainingItems,
            ];
        });

        return $result;
    }

    /**
     * @return array{correct_answers:int,total_words:int,incorrect_items:Collection<int, GameSessionItem>}
     */
    public function resultSummary(GameSession $gameSession): array
    {
        return $this->gameResultSummaryService->summarize($gameSession);
    }
}
