<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Support\PartOfSpeechCatalog;

class TelegramGameQuestionSender
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramGameRunCallbackData $callbackData,
    ) {
    }

    public function send(TelegramGameRun $run, TelegramGameRunItem $item): array
    {
        return $this->bot->sendMessage(
            (string) $run->user->tg_chat_id,
            $this->buildQuestionText($run, $item),
            [
                'reply_markup' => [
                    'inline_keyboard' => $this->buildOptionsKeyboard($run, $item),
                ],
            ],
        );
    }

    private function buildQuestionText(TelegramGameRun $run, TelegramGameRunItem $item): string
    {
        $progressLine = "Вопрос {$item->order_index} из {$run->total_words}";
        $directionLine = $run->direction === 'foreign_to_ru'
            ? 'Переведите на русский:'
            : 'Переведите на иностранный:';

        $lines = [
            $progressLine,
            $directionLine,
            $item->prompt_text,
        ];

        $partOfSpeechLabel = PartOfSpeechCatalog::label($item->part_of_speech_snapshot);

        if ($partOfSpeechLabel !== null) {
            $lines[] = "Часть речи: {$partOfSpeechLabel}";
        }

        $options = collect($item->options_json ?? [])
            ->map(static fn ($option): string => (string) $option)
            ->values();

        if ($options->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Варианты ответа:';

            foreach ($options as $index => $option) {
                $lines[] = ($index + 1).'. '.$option;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<int, array{text:string,callback_data:string}>>
     */
    private function buildOptionsKeyboard(TelegramGameRun $run, TelegramGameRunItem $item): array
    {
        $options = collect($item->options_json ?? [])
            ->map(static fn ($option): string => (string) $option)
            ->values();

        return $options
            ->map(fn (string $option, int $index): array => [
                'text' => (string) ($index + 1),
                'callback_data' => $this->callbackData->makeAnswer($run->id, $item->id, $index),
            ])
            ->chunk(3)
            ->map(static fn ($chunk): array => $chunk->values()->all())
            ->values()
            ->all();
    }
}
