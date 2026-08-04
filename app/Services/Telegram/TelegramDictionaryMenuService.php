<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Models\UserDictionary;
use App\Services\DictionarySubscriptions\DictionaryAccessService;
use App\Services\Favorites\FavoriteWordsService;
use Illuminate\Support\Collection;

class TelegramDictionaryMenuService
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService,
        private readonly TelegramDictionaryCallbackData $telegramDictionaryCallbackData,
        private readonly DictionaryAccessService $dictionaryAccessService,
        private readonly FavoriteWordsService $favoriteWordsService,
    ) {
    }

    /**
     * @return array{status:string,dictionaries_count:int}
     */
    public function show(User $user, string $chatId, ?int $messageId = null): array
    {
        $favoriteDictionary = [
            'id' => $this->favoriteWordsService->virtualDictionaryId(),
            'name' => __('dictionaries.index.favorites.name'),
            'language' => null,
        ];

        $dictionaries = $this->dictionaryAccessService
            ->accessibleDictionariesQuery($user)
            ->orderBy('id')
            ->get(['user_dictionaries.id', 'user_dictionaries.name', 'user_dictionaries.language']);

        /** @var Collection<int, array{id:int,name:string,language:?string}> $items */
        $items = collect([$favoriteDictionary])
            ->concat(
                $dictionaries->map(static fn (UserDictionary $dictionary): array => [
                    'id' => (int) $dictionary->id,
                    'name' => (string) $dictionary->name,
                    'language' => filled($dictionary->language) ? (string) $dictionary->language : null,
                ])
            )
            ->values();

        $lines = ['Ваши словари:', ''];

        foreach ($items as $index => $dictionary) {
            $number = $index + 1;
            $line = "{$number}. {$dictionary['name']}";

            if (filled($dictionary['language'])) {
                $line .= " - {$dictionary['language']}";
            }

            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Выберите словарь:';

        $keyboard = $items
            ->map(function (array $dictionary, int $index): array {
                return [
                    'text' => (string) ($index + 1),
                    'callback_data' => $this->telegramDictionaryCallbackData->makeShow((int) $dictionary['id'], 1),
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
            'dictionaries_count' => $items->count(),
        ];
    }
}
