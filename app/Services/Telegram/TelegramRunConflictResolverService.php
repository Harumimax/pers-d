<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramRunConflictResolverService
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService,
        private readonly TelegramGameRunMonitorService $telegramGameRunMonitorService,
    ) {
    }

    public function expireRunsBeforeSchedulingNext(User $user, CarbonImmutable $scheduledFor): int
    {
        $activeRuns = TelegramGameRun::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                TelegramGameRun::STATUS_AWAITING_START,
                TelegramGameRun::STATUS_IN_PROGRESS,
            ])
            ->where('scheduled_for', '<', $scheduledFor)
            ->orderBy('scheduled_for')
            ->get();

        if ($activeRuns->isEmpty()) {
            return 0;
        }

        foreach ($activeRuns as $run) {
            $this->telegramGameRunMonitorService->markExpiredBecauseNewSessionStarted($run);
        }

        $this->notifyUserAboutExpiredRuns($user, $activeRuns->count());

        Log::info('telegram.scheduler.previous_runs_expired_for_new_session', [
            'user_id' => $user->id,
            'expired_runs_count' => $activeRuns->count(),
            'scheduled_for' => $scheduledFor->toIso8601String(),
            'expired_run_ids' => $activeRuns->pluck('id')->all(),
        ]);

        return $activeRuns->count();
    }

    private function notifyUserAboutExpiredRuns(User $user, int $expiredRunsCount): void
    {
        $chatId = trim((string) $user->tg_chat_id);

        if ($chatId === '') {
            return;
        }

        $text = $expiredRunsCount === 1
            ? 'Так как настало время для новой сессии, предыдущая сессия будет закрыта.'
            : 'Так как настало время для новой сессии, предыдущие незавершённые сессии будут закрыты.';

        try {
            $this->telegramBotService->sendMessage($chatId, $text);
        } catch (Throwable $exception) {
            Log::warning('telegram.scheduler.previous_runs_expire_notice_failed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'expired_runs_count' => $expiredRunsCount,
                'message' => $exception->getMessage(),
            ]);

            report($exception);
        }
    }
}
