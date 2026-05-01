<?php

namespace App\Console\Commands;

use App\Models\TelegramIntervalReviewRun;
use App\Services\Telegram\TelegramIntervalReviewRunMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleTelegramIntervalReviewRunsCommand extends Command
{
    protected $signature = 'telegram:cleanup-stale-interval-review-runs';

    protected $description = 'Mark stale interval review Telegram runs as expired or abandoned.';

    public function handle(TelegramIntervalReviewRunMonitorService $telegramIntervalReviewRunMonitorService): int
    {
        $expiredCount = 0;
        $abandonedCount = 0;
        $threshold = now()->subHours(12);

        TelegramIntervalReviewRun::query()
            ->with('session')
            ->where('status', TelegramIntervalReviewRun::STATUS_AWAITING_START)
            ->where(function ($query) use ($threshold): void {
                $query->where('intro_message_sent_at', '<=', $threshold)
                    ->orWhere(function ($nested) use ($threshold): void {
                        $nested->whereNull('intro_message_sent_at')
                            ->where('created_at', '<=', $threshold);
                    });
            })
            ->chunkById(100, function ($runs) use ($telegramIntervalReviewRunMonitorService, &$expiredCount): void {
                foreach ($runs as $run) {
                    $telegramIntervalReviewRunMonitorService->markExpired($run);
                    $expiredCount++;
                }
            });

        TelegramIntervalReviewRun::query()
            ->with('session')
            ->where('status', TelegramIntervalReviewRun::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($threshold): void {
                $query->where('last_interaction_at', '<=', $threshold)
                    ->orWhere(function ($nested) use ($threshold): void {
                        $nested->whereNull('last_interaction_at')
                            ->where('started_at', '<=', $threshold);
                    });
            })
            ->chunkById(100, function ($runs) use ($telegramIntervalReviewRunMonitorService, &$abandonedCount): void {
                foreach ($runs as $run) {
                    $telegramIntervalReviewRunMonitorService->markAbandoned($run);
                    $abandonedCount++;
                }
            });

        Log::info('telegram.interval_review.cleanup_stale_runs', [
            'expired_count' => $expiredCount,
            'abandoned_count' => $abandonedCount,
            'threshold' => $threshold->toIso8601String(),
        ]);

        $this->info("Expired interval runs: {$expiredCount}. Abandoned interval runs: {$abandonedCount}.");

        return self::SUCCESS;
    }
}
