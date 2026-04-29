<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Models\UserDictionary;

class TelegramDictionaryMenuService
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService,
        private readonly TelegramDictionaryCallbackData $telegramDictionaryCallbackData,
    ) {
    }

    /**
     * @return array{status:string,dictionaries_count:int}
     */
    public function show(User $user, string $chatId, ?int $messageId = null): array
    {
        $dictionaries = UserDictionary::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get(['id', 'name', 'language']);

        if ($dictionaries->isEmpty()) {
            $text = 'У вас пока нет созданных словарей, сделать это можно на https://wordkeeper.space';

            if ($messageId !== null) {
                $this->telegramBotService->editMessageText($chatId, $messageId, $text);
            } else {
                $this->telegramBotService->sendMessage($chatId, $text);
            }

            return [
                'status' => 'empty',
                'dictionaries_count' => 0,
            ];
        }

        $lines = ['Ваши словари:', ''];

        foreach ($dictionaries as $index => $dictionary) {
            $number = $index + 1;
            $line = "{$number}. {$dictionary->name}";

            if (filled($dictionary->language)) {
                $line .= " — {$dictionary->language}";
            }

            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Выберите словарь:';

        $keyboard = collect($dictionaries)
            ->values()
            ->map(function (UserDictionary $dictionary, int $index): array {
                return [
                    'text' => (string) ($index + 1),
                    'callback_data' => $this->telegramDictionaryCallbackData->makeShow($dictionary->id, 1),
                ];
            })
            ->chunk(4)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $payload = [
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ];

        $text = implode("\n", $lines);

        if ($messageId !== null) {
            $this->telegramBotService->editMessageText($chatId, $messageId, $text, $payload);
        } else {
            $this->telegramBotService->sendMessage($chatId, $text, $payload);
        }

        return [
            'status' => 'shown',
            'dictionaries_count' => $dictionaries->count(),
        ];
    }
}
