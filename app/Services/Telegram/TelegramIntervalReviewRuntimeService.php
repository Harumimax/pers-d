<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewSession;
use Illuminate\Support\Facades\DB;

class TelegramIntervalReviewRuntimeService
{
    /**
     * @return array{status:string,run:TelegramIntervalReviewRun}
     */
    public function startRun(TelegramIntervalReviewRun $run): array
    {
        return DB::transaction(function () use ($run): array {
            /** @var TelegramIntervalReviewRun $lockedRun */
            $lockedRun = TelegramIntervalReviewRun::query()
                ->with(['session'])
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
                'run' => $lockedRun->fresh(['session']),
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
}
