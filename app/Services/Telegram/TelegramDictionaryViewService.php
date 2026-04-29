<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Models\UserDictionary;

class TelegramDictionaryViewService
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly TelegramBotService $telegramBotService,
        private readonly TelegramDictionaryCallbackData $telegramDictionaryCallbackData,
    ) {
    }

    /**
     * @return array{status:string,dictionary:?UserDictionary,page:int,total_pages:int}
     */
    public function show(User $user, int $dictionaryId, int $page, string $chatId, int $messageId): array
    {
        $dictionary = UserDictionary::query()
            ->where('user_id', $user->id)
            ->find($dictionaryId);

        if (! $dictionary instanceof UserDictionary) {
            return [
                'status' => 'not_found',
                'dictionary' => null,
                'page' => 1,
                'total_pages' => 1,
            ];
        }

        $words = $dictionary->words()
            ->orderBy('words.id')
            ->get([
                'words.word',
                'words.part_of_speech',
                'words.translation',
                'words.comment',
            ]);

        $totalWords = $words->count();
        $totalPages = max(1, (int) ceil($totalWords / self::PER_PAGE));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * self::PER_PAGE;
        $pageWords = $words->slice($offset, self::PER_PAGE)->values();
        $from = $totalWords === 0 ? 0 : $offset + 1;
        $to = $totalWords === 0 ? 0 : min($offset + self::PER_PAGE, $totalWords);

        $lines = [
            "Словарь: {$dictionary->name}",
        ];

        if (filled($dictionary->language)) {
            $lines[] = "Язык: {$dictionary->language}";
        }

        if ($totalWords === 0) {
            $lines[] = 'В этом словаре пока нет слов.';
        } else {
            $lines[] = "Слова {$from}–{$to} из {$totalWords}";
            $lines[] = '';

            foreach ($pageWords as $index => $word) {
                $number = $offset + $index + 1;
                $lines[] = "{$number}. {$word->word}";

                if (filled($word->part_of_speech)) {
                    $lines[] = "Часть речи: {$word->part_of_speech}";
                }

                $lines[] = "Перевод: {$word->translation}";

                if (filled($word->comment)) {
                    $lines[] = "Комментарий: {$word->comment}";
                }

                if ($index !== $pageWords->count() - 1) {
                    $lines[] = '';
                }
            }
        }

        $keyboard = [
            [
                [
                    'text' => '← Назад',
                    'callback_data' => $page > 1
                        ? $this->telegramDictionaryCallbackData->makePage($dictionary->id, $page - 1)
                        : $this->telegramDictionaryCallbackData->makeNoop(),
                ],
                [
                    'text' => "{$page}/{$totalPages}",
                    'callback_data' => $this->telegramDictionaryCallbackData->makeNoop(),
                ],
                [
                    'text' => 'Вперёд →',
                    'callback_data' => $page < $totalPages
                        ? $this->telegramDictionaryCallbackData->makePage($dictionary->id, $page + 1)
                        : $this->telegramDictionaryCallbackData->makeNoop(),
                ],
            ],
            [[
                'text' => 'К словарям',
                'callback_data' => $this->telegramDictionaryCallbackData->makeBack(),
            ]],
        ];

        $this->telegramBotService->editMessageText(
            $chatId,
            $messageId,
            implode("\n", $lines),
            [
                'reply_markup' => [
                    'inline_keyboard' => $keyboard,
                ],
            ],
        );

        return [
            'status' => 'shown',
            'dictionary' => $dictionary,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }
}
