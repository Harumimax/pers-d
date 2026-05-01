<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewSession;
use Carbon\CarbonImmutable;

class TelegramIntervalReviewRunNotifier
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramIntervalReviewRunCallbackData $callbackData,
    ) {
    }

    public function sendIntro(TelegramIntervalReviewRun $run): TelegramIntervalReviewRun
    {
        $response = $this->bot->sendMessage(
            (string) $run->user->tg_chat_id,
            $this->introText($run),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => 'Начать',
                            'callback_data' => $this->callbackData->make(TelegramIntervalReviewRunCallbackData::ACTION_START, $run->id),
                        ],
                        [
                            'text' => 'Отменить',
                            'callback_data' => $this->callbackData->make(TelegramIntervalReviewRunCallbackData::ACTION_CANCEL, $run->id),
                        ],
                    ]],
                ],
            ]
        );

        $messageId = data_get($response, 'result.message_id');

        $run->forceFill([
            'status' => TelegramIntervalReviewRun::STATUS_AWAITING_START,
            'intro_message_sent_at' => CarbonImmutable::now('UTC'),
            'intro_message_id' => is_numeric($messageId) ? (int) $messageId : null,
            'last_interaction_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'last_error_at' => null,
        ])->save();

        $run->session->forceFill([
            'status' => TelegramIntervalReviewSession::STATUS_AWAITING_START,
        ])->save();

        return $run->fresh(['user', 'plan', 'session', 'items']);
    }

    private function introText(TelegramIntervalReviewRun $run): string
    {
        return sprintf(
            '%s сессия интервального повторения слов. Подготовлено %d слов.',
            $this->sessionLabel($run->session_number),
            $run->total_words,
        );
    }

    private function sessionLabel(int $sessionNumber): string
    {
        return match ($sessionNumber) {
            1 => 'Первая',
            2 => 'Вторая',
            3 => 'Третья',
            4 => 'Четвёртая',
            5 => 'Пятая',
            6 => 'Шестая',
            default => "Сессия {$sessionNumber}",
        };
    }
}
