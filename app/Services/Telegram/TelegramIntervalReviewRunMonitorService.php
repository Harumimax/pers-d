<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewSession;

class TelegramIntervalReviewRunMonitorService
{
    public function touchInteraction(TelegramIntervalReviewRun $run): TelegramIntervalReviewRun
    {
        $run->forceFill([
            'last_interaction_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'last_error_at' => null,
        ])->save();

        return $run->fresh(['session', 'user', 'items', 'plan.sessions']);
    }

    public function recordFailure(
        TelegramIntervalReviewRun $run,
        string $code,
        string $message,
        ?string $runStatus = null,
        ?string $sessionStatus = null,
    ): TelegramIntervalReviewRun {
        $attributes = [
            'last_error_code' => $code,
            'last_error_message' => mb_substr($message, 0, 2000),
            'last_error_at' => now(),
        ];

        if ($runStatus !== null) {
            $attributes['status'] = $runStatus;
        }

        $run->forceFill($attributes)->save();

        if ($sessionStatus !== null) {
            $run->session->forceFill([
                'status' => $sessionStatus,
            ])->save();
        }

        return $run->fresh(['session', 'user', 'items', 'plan.sessions']);
    }

    public function markExpired(TelegramIntervalReviewRun $run): TelegramIntervalReviewRun
    {
        return $this->recordFailure(
            $run,
            'expired',
            'Interval review run expired after waiting for start longer than 12 hours.',
            TelegramIntervalReviewRun::STATUS_EXPIRED,
            TelegramIntervalReviewSession::STATUS_EXPIRED,
        );
    }

    public function markAbandoned(TelegramIntervalReviewRun $run): TelegramIntervalReviewRun
    {
        return $this->recordFailure(
            $run,
            'abandoned',
            'Interval review run abandoned after 12 hours without Telegram activity.',
            TelegramIntervalReviewRun::STATUS_ABANDONED,
            TelegramIntervalReviewSession::STATUS_ABANDONED,
        );
    }
}
