<?php

namespace App\Services\Telegram;

class TelegramIntervalReviewRunCallbackData
{
    public const ACTION_START = 'start';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_BEGIN_QUIZ = 'begin_quiz';
    public const ACTION_ANSWER = 'answer';

    public function make(string $action, int $runId): string
    {
        return 'interval_run:'.$action.':'.$runId;
    }

    public function makeAnswer(int $runId, int $itemId, int $optionIndex): string
    {
        return "interval_answer:{$runId}:{$itemId}:{$optionIndex}";
    }

    /**
     * @return array{type:string,action?:string,run_id:int,item_id?:int,option_index?:int}|null
     */
    public function parse(string $data): ?array
    {
        $parts = explode(':', $data);

        if (count($parts) === 3 && $parts[0] === 'interval_run') {
            [, $action, $runId] = $parts;

            if (! in_array($action, [self::ACTION_START, self::ACTION_CANCEL, self::ACTION_BEGIN_QUIZ], true)) {
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

        if (count($parts) !== 4 || $parts[0] !== 'interval_answer') {
            return null;
        }

        [, $runId, $itemId, $optionIndex] = $parts;

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
}
