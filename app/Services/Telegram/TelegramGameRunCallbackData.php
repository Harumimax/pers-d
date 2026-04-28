<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;

class TelegramGameRunCallbackData
{
    public const ACTION_START = 'start';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_ANSWER = 'answer';

    public function make(string $action, int $runId): string
    {
        return 'telegram_run:'.$action.':'.$runId;
    }

    public function makeAnswer(int $runId, int $itemId, int $optionIndex): string
    {
        return 'telegram_answer:'.$runId.':'.$itemId.':'.$optionIndex;
    }

    /**
     * @return array{type:string,action?:string,run_id:int,item_id?:int,option_index?:int}|null
     */
    public function parse(string $data): ?array
    {
        $parts = explode(':', $data);

        if (count($parts) === 3 && $parts[0] === 'telegram_run') {
            [$prefix, $action, $runId] = $parts;

            if (! in_array($action, [self::ACTION_START, self::ACTION_CANCEL], true)) {
                return null;
            }

            if (! ctype_digit($runId)) {
                return null;
            }

            return [
                'type' => 'run_action',
                'action' => $action,
                'run_id' => (int) $runId,
            ];
        }

        if (count($parts) === 4 && $parts[0] === 'telegram_answer') {
            [$prefix, $runId, $itemId, $optionIndex] = $parts;

            if (! ctype_digit($runId) || ! ctype_digit($itemId) || ! ctype_digit($optionIndex)) {
                return null;
            }

            return [
                'type' => self::ACTION_ANSWER,
                'run_id' => (int) $runId,
                'item_id' => (int) $itemId,
                'option_index' => (int) $optionIndex,
            ];
        }

        return null;
    }

    public function isAwaitingStart(TelegramGameRun $run): bool
    {
        return $run->status === TelegramGameRun::STATUS_AWAITING_START;
    }
}
