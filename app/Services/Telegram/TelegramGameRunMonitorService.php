<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;

class TelegramGameRunMonitorService
{
    public function touchInteraction(TelegramGameRun $run): TelegramGameRun
    {
        $run->forceFill([
            'last_interaction_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'last_error_at' => null,
        ])->save();

        return $run->fresh();
    }

    public function recordFailure(
        TelegramGameRun $run,
        string $code,
        string $message,
        ?string $status = null,
    ): TelegramGameRun {
        $attributes = [
            'last_error_code' => $code,
            'last_error_message' => mb_substr($message, 0, 2000),
            'last_error_at' => now(),
        ];

        if ($status !== null) {
            $attributes['status'] = $status;
        }

        $run->forceFill($attributes)->save();

        return $run->fresh();
    }

    public function markExpired(TelegramGameRun $run): TelegramGameRun
    {
        return $this->recordFailure(
            $run,
            'expired',
            'Run expired after waiting for start longer than 12 hours.',
            TelegramGameRun::STATUS_EXPIRED,
        );
    }

    public function markExpiredBecauseNewSessionStarted(TelegramGameRun $run): TelegramGameRun
    {
        return $this->recordFailure(
            $run,
            'expired_by_new_session',
            'Run expired because a newer scheduled Telegram session started.',
            TelegramGameRun::STATUS_EXPIRED,
        );
    }

    public function markAbandoned(TelegramGameRun $run): TelegramGameRun
    {
        return $this->recordFailure(
            $run,
            'abandoned',
            'Run abandoned after 12 hours without Telegram activity.',
            TelegramGameRun::STATUS_ABANDONED,
        );
    }
}
