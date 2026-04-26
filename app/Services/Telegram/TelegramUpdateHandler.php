<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramAuthStateStore $stateStore,
    ) {
    }

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $chatId = $this->extractChatId($message);
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === null || $text === '') {
            return;
        }

        $username = $this->sanitizeTelegramUsername($message['from']['username'] ?? null);

        if ($text === '/start') {
            $this->stateStore->clear($chatId);
            $this->sendStartMessage($chatId);

            return;
        }

        if ($text === '/login') {
            $this->stateStore->start($chatId);
            $this->bot->sendMessage($chatId, "Введите email от аккаунта WordKeeper.");

            return;
        }

        if ($text === 'Выход' || $text === '/logout') {
            $this->stateStore->clear($chatId);
            $this->handleLogout($chatId);

            return;
        }

        $state = $this->stateStore->get($chatId);

        if ($state === null) {
            $this->bot->sendMessage($chatId, "Отправьте /start, чтобы увидеть доступные команды.");

            return;
        }

        if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_EMAIL) {
            $this->handleEmailStep($chatId, $text);

            return;
        }

        if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_PASSWORD) {
            $this->handlePasswordStep($chatId, $text, $state['email'], $username);
        }
    }

    private function handleEmailStep(string $chatId, string $text): void
    {
        $email = mb_strtolower(trim($text));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->bot->sendMessage($chatId, "Нужен корректный email. Попробуйте ещё раз.");

            return;
        }

        $this->stateStore->storeEmail($chatId, $email);

        $this->bot->sendMessage($chatId, "Теперь введите пароль от аккаунта сайта.");
    }

    private function handlePasswordStep(string $chatId, string $password, ?string $email, ?string $username): void
    {
        $this->stateStore->clear($chatId);

        if ($email === null || $email === '') {
            $this->bot->sendMessage($chatId, "Сессия входа истекла. Отправьте /login ещё раз.");

            return;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user instanceof User || ! Hash::check($password, (string) $user->password)) {
            $this->bot->sendMessage($chatId, "Не удалось выполнить вход. Проверьте данные и начните заново через /login.");

            return;
        }

        DB::transaction(function () use ($chatId, $user, $username): void {
            User::query()
                ->where('tg_chat_id', $chatId)
                ->whereKeyNot($user->getKey())
                ->update([
                    'tg_chat_id' => null,
                    'tg_linked_at' => null,
                ]);

            $attributes = [
                'tg_chat_id' => $chatId,
                'tg_linked_at' => now(),
            ];

            if ($username !== null && $username !== '') {
                $attributes['tg_login'] = $username;
            }

            $user->forceFill($attributes)->save();
        });

        $this->bot->sendMessage(
            $chatId,
            "Telegram успешно подключён к вашему аккаунту WordKeeper.",
            [
                'reply_markup' => [
                    'keyboard' => [
                        [['text' => 'Выход']],
                    ],
                    'resize_keyboard' => true,
                ],
            ]
        );
    }

    private function handleLogout(string $chatId): void
    {
        User::query()
            ->where('tg_chat_id', $chatId)
            ->update([
                'tg_chat_id' => null,
                'tg_linked_at' => null,
            ]);

        $this->bot->sendMessage(
            $chatId,
            "Telegram-аккаунт отключён. Для повторной привязки отправьте /login.",
            [
                'reply_markup' => [
                    'remove_keyboard' => true,
                ],
            ]
        );
    }

    private function sendStartMessage(string $chatId): void
    {
        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", [
                "Это Telegram-бот WordKeeper.",
                "Он нужен для будущей работы со словарями и тренировками прямо из Telegram.",
                "Чтобы подключить бота к вашему аккаунту сайта, отправьте /login.",
            ])
        );
    }

    private function extractChatId(array $message): ?string
    {
        $chatId = $message['chat']['id'] ?? null;

        if ($chatId === null) {
            return null;
        }

        return (string) $chatId;
    }

    private function sanitizeTelegramUsername(mixed $username): ?string
    {
        if (! is_string($username)) {
            return null;
        }

        $username = ltrim(trim($username), '@');

        return $username !== '' ? $username : null;
    }
}
