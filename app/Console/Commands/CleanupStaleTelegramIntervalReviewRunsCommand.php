<?php

namespace App\Console\Commands;

use App\Models\TelegramIntervalReviewRun;
use App\Services\Telegram\TelegramIntervalReviewRunMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleTelegramIntervalReviewRunsCommand extends Command
{
    protected $signature = 'telegram:cleanup-stale-interval-review-runs';

    protected $description = 'Mark superseded interval review Telegram runs as expired once a newer session exists.';

    public function handle(TelegramIntervalReviewRunMonitorService $telegramIntervalReviewRunMonitorService): int
    {
        $expiredCount = 0;

        TelegramIntervalReviewRun::query()
            ->with('session')
            ->whereIn('status', [
                TelegramIntervalReviewRun::STATUS_AWAITING_START,
                TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
            ])
            ->chunkById(100, function ($runs) use ($telegramIntervalReviewRunMonitorService, &$expiredCount): void {
                foreach ($runs as $run) {
                    if (! $this->hasNewerRun($run)) {
                        continue;
                    }

                    $telegramIntervalReviewRunMonitorService->markExpired($run);
                    $expiredCount++;
                }
            });

        Log::info('telegram.interval_review.cleanup_stale_runs', [
            'expired_count' => $expiredCount,
        ]);

        $this->info("Expired interval runs: {$expiredCount}.");

        return self::SUCCESS;
    }

    private function hasNewerRun(TelegramIntervalReviewRun $run): bool
    {
        $scheduledFor = $run->scheduled_for ?? $run->created_at;

        if ($scheduledFor === null) {
            return false;
        }

        return TelegramIntervalReviewRun::query()
            ->where('telegram_interval_review_plan_id', $run->telegram_interval_review_plan_id)
            ->whereKeyNot($run->id)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>', $scheduledFor)
            ->exists();
    }
}
