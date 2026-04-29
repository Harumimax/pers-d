<?php

namespace App\Console\Commands;

use App\Models\TelegramGameRun;
use App\Services\Telegram\TelegramGameRunMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleTelegramRunsCommand extends Command
{
    protected $signature = 'telegram:cleanup-stale-runs';

    protected $description = 'Mark old awaiting_start Telegram runs as expired and inactive in_progress runs as abandoned.';

    public function handle(TelegramGameRunMonitorService $telegramGameRunMonitorService): int
    {
        $expiredCount = 0;
        $abandonedCount = 0;
        $threshold = now()->subHours(12);

        TelegramGameRun::query()
            ->where('status', TelegramGameRun::STATUS_AWAITING_START)
            ->where(function ($query) use ($threshold): void {
                $query->where('intro_message_sent_at', '<=', $threshold)
                    ->orWhere(function ($nested) use ($threshold): void {
                        $nested->whereNull('intro_message_sent_at')
                            ->where('created_at', '<=', $threshold);
                    });
            })
            ->chunkById(100, function ($runs) use ($telegramGameRunMonitorService, &$expiredCount): void {
                foreach ($runs as $run) {
                    $telegramGameRunMonitorService->markExpired($run);
                    $expiredCount++;
                }
            });

        TelegramGameRun::query()
            ->where('status', TelegramGameRun::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($threshold): void {
                $query->where('last_interaction_at', '<=', $threshold)
                    ->orWhere(function ($nested) use ($threshold): void {
                        $nested->whereNull('last_interaction_at')
                            ->where('started_at', '<=', $threshold);
                    });
            })
            ->chunkById(100, function ($runs) use ($telegramGameRunMonitorService, &$abandonedCount): void {
                foreach ($runs as $run) {
                    $telegramGameRunMonitorService->markAbandoned($run);
                    $abandonedCount++;
                }
            });

        Log::info('telegram.scheduler.cleanup_stale_runs', [
            'expired_count' => $expiredCount,
            'abandoned_count' => $abandonedCount,
            'threshold' => $threshold->toIso8601String(),
        ]);

        $this->info("Expired: {$expiredCount}. Abandoned: {$abandonedCount}.");

        return self::SUCCESS;
    }
}
