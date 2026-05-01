<?php

namespace App\Services\Telegram;

class TelegramIntervalReviewRunCallbackData
{
    public const ACTION_START = 'start';
    public const ACTION_CANCEL = 'cancel';

    public function make(string $action, int $runId): string
    {
        return 'interval_run:'.$action.':'.$runId;
    }

    /**
     * @return array{action:string,run_id:int}|null
     */
    public function parse(string $data): ?array
    {
        $parts = explode(':', $data);

        if (count($parts) !== 3 || $parts[0] !== 'interval_run') {
            return null;
        }

        [, $action, $runId] = $parts;

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
}
