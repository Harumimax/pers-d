<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewRun;
use App\Support\PartOfSpeechCatalog;

class TelegramIntervalReviewWordListSender
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramIntervalReviewRunCallbackData $callbackData,
    ) {
    }

    public function send(TelegramIntervalReviewRun $run): array
    {
        return $this->bot->sendMessage(
            (string) $run->user->tg_chat_id,
            $this->buildText($run),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => 'Начать квиз',
                            'callback_data' => $this->callbackData->make(TelegramIntervalReviewRunCallbackData::ACTION_BEGIN_QUIZ, $run->id),
                        ],
                    ]],
                ],
            ],
        );
    }

    private function buildText(TelegramIntervalReviewRun $run): string
    {
        $lines = [
            "Слова этой сессии ({$run->session_number}/6):",
            '',
        ];

        foreach ($run->items as $index => $item) {
            $lines[] = ($index + 1).'. '.$item->word_snapshot;

            $partOfSpeechLabel = PartOfSpeechCatalog::label($item->part_of_speech_snapshot);

            if ($partOfSpeechLabel !== null) {
                $lines[] = "Часть речи: {$partOfSpeechLabel}";
            }

            $lines[] = "Перевод: {$item->translation_snapshot}";

            if (filled($item->comment_snapshot)) {
                $lines[] = "Комментарий: {$item->comment_snapshot}";
            }

            if ($index < $run->items->count() - 1) {
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
