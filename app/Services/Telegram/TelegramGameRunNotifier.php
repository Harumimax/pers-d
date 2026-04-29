<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use Carbon\CarbonImmutable;

class TelegramGameRunNotifier
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramGameRunCallbackData $callbackData,
        private readonly TelegramGameRunMonitorService $telegramGameRunMonitorService,
    ) {
    }

    public function sendIntro(TelegramGameRun $run): TelegramGameRun
    {
        $response = $this->bot->sendMessage(
            (string) $run->user->tg_chat_id,
            "Пришло время повторить слова. Запланировано к повторению {$run->total_words} слов.",
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => 'Начать',
                            'callback_data' => $this->callbackData->make(TelegramGameRunCallbackData::ACTION_START, $run->id),
                        ],
                        [
                            'text' => 'Отмена',
                            'callback_data' => $this->callbackData->make(TelegramGameRunCallbackData::ACTION_CANCEL, $run->id),
                        ],
                    ]],
                ],
            ]
        );

        $messageId = data_get($response, 'result.message_id');

        $run->forceFill([
            'status' => TelegramGameRun::STATUS_AWAITING_START,
            'intro_message_sent_at' => CarbonImmutable::now('UTC'),
            'intro_message_id' => is_numeric($messageId) ? (int) $messageId : null,
            'last_interaction_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'last_error_at' => null,
        ])->save();

        return $this->telegramGameRunMonitorService->touchInteraction($run);
    }
}
