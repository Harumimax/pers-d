<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;

class TelegramGameRunCallbackData
{
    public const ACTION_START = 'start';
    public const ACTION_CANCEL = 'cancel';

    public function make(string $action, int $runId): string
    {
        return 'telegram_run:'.$action.':'.$runId;
    }

    /**
     * @return array{action:string,run_id:int}|null
     */
    public function parse(string $data): ?array
    {
        $parts = explode(':', $data, 3);

        if (count($parts) !== 3 || $parts[0] !== 'telegram_run') {
            return null;
        }

        [$prefix, $action, $runId] = $parts;

        if (! in_array($action, [self::ACTION_START, self::ACTION_CANCEL], true)) {
            return null;
        }

        if (! ctype_digit($runId)) {
            return null;
        }

        return [
            'action' => $action,
            'run_id' => (int) $runId,
        ];
    }

    public function isAwaitingStart(TelegramGameRun $run): bool
    {
        return $run->status === TelegramGameRun::STATUS_AWAITING_START;
    }
}
