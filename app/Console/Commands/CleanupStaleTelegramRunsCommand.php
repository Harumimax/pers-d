<?php

namespace App\Console\Commands;

use App\Models\TelegramGameRun;
use App\Services\Telegram\TelegramGameRunMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleTelegramRunsCommand extends Command
{
    protected $signature = 'telegram:cleanup-stale-runs';

    protected $description = 'Mark superseded Telegram runs as expired once a newer session exists.';

    public function handle(TelegramGameRunMonitorService $telegramGameRunMonitorService): int
    {
        $expiredCount = 0;

        TelegramGameRun::query()
            ->whereIn('status', [
                TelegramGameRun::STATUS_AWAITING_START,
                TelegramGameRun::STATUS_IN_PROGRESS,
            ])
            ->chunkById(100, function ($runs) use ($telegramGameRunMonitorService, &$expiredCount): void {
                foreach ($runs as $run) {
                    if (! $this->hasNewerRun($run)) {
                        continue;
                    }

                    $telegramGameRunMonitorService->markExpired($run);
                    $expiredCount++;
                }
            });

        Log::info('telegram.scheduler.cleanup_stale_runs', [
            'expired_count' => $expiredCount,
        ]);

        $this->info("Expired: {$expiredCount}.");

        return self::SUCCESS;
    }

    private function hasNewerRun(TelegramGameRun $run): bool
    {
        $scheduledFor = $run->scheduled_for ?? $run->created_at;

        if ($scheduledFor === null) {
            return false;
        }

        return TelegramGameRun::query()
            ->where('user_id', $run->user_id)
            ->whereKeyNot($run->id)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>', $scheduledFor)
            ->exists();
    }
}
