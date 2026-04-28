<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramAuthStateStore $stateStore,
        private readonly TelegramGameRunCallbackData $telegramGameRunCallbackData,
    ) {
    }

    public function handle(array $update): void
    {
        $callbackQuery = $update['callback_query'] ?? null;

        if (is_array($callbackQuery)) {
            $this->handleCallbackQuery($callbackQuery);

            return;
        }

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
            $this->bot->sendMessage($chatId, 'Введите email от аккаунта WordKeeper.');

            return;
        }

        if ($text === 'Выход' || $text === '/logout') {
            $this->stateStore->clear($chatId);
            $this->handleLogout($chatId);

            return;
        }

        $state = $this->stateStore->get($chatId);

        if ($state === null) {
            $this->bot->sendMessage($chatId, 'Отправьте /start, чтобы увидеть доступные команды.');

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

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryId = trim((string) ($callbackQuery['id'] ?? ''));
        $payload = $this->telegramGameRunCallbackData->parse(trim((string) ($callbackQuery['data'] ?? '')));
        $chatId = $this->extractCallbackChatId($callbackQuery);
        $messageId = isset($callbackQuery['message']['message_id']) && is_numeric($callbackQuery['message']['message_id'])
            ? (int) $callbackQuery['message']['message_id']
            : null;

        if ($callbackQueryId === '' || $payload === null || $chatId === null) {
            if ($callbackQueryId !== '') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Действие недоступно.');
            }

            return;
        }

        /** @var TelegramGameRun|null $run */
        $run = TelegramGameRun::query()
            ->with('user')
            ->find($payload['run_id']);

        if (! $run instanceof TelegramGameRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия не найдена.');

            return;
        }

        if ($payload['action'] === TelegramGameRunCallbackData::ACTION_CANCEL) {
            $this->cancelRun($run, $callbackQueryId, $chatId, $messageId);

            return;
        }

        if ($payload['action'] === TelegramGameRunCallbackData::ACTION_START) {
            $this->startRun($run, $callbackQueryId, $chatId, $messageId);
        }
    }

    private function cancelRun(TelegramGameRun $run, string $callbackQueryId, string $chatId, ?int $messageId): void
    {
        /** @var TelegramGameRun $freshRun */
        $freshRun = DB::transaction(function () use ($run): TelegramGameRun {
            /** @var TelegramGameRun $lockedRun */
            $lockedRun = TelegramGameRun::query()
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status === TelegramGameRun::STATUS_CANCELLED) {
                return $lockedRun;
            }

            if ($lockedRun->status !== TelegramGameRun::STATUS_AWAITING_START) {
                return $lockedRun;
            }

            $lockedRun->forceFill([
                'status' => TelegramGameRun::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            return $lockedRun;
        });

        if ($freshRun->status === TelegramGameRun::STATUS_CANCELLED) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия отменена.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Текущая Telegram-сессия отменена.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Эту сессию уже нельзя отменить.');
    }

    private function startRun(TelegramGameRun $run, string $callbackQueryId, string $chatId, ?int $messageId): void
    {
        /** @var TelegramGameRun $freshRun */
        $freshRun = DB::transaction(function () use ($run): TelegramGameRun {
            /** @var TelegramGameRun $lockedRun */
            $lockedRun = TelegramGameRun::query()
                ->whereKey($run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRun->status === TelegramGameRun::STATUS_IN_PROGRESS) {
                return $lockedRun;
            }

            if ($lockedRun->status !== TelegramGameRun::STATUS_AWAITING_START) {
                return $lockedRun;
            }

            $lockedRun->forceFill([
                'status' => TelegramGameRun::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ])->save();

            return $lockedRun;
        });

        if ($freshRun->status === TelegramGameRun::STATUS_IN_PROGRESS) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия запущена.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Сессия подготовлена. Полный игровой поток будет подключён следующим этапом.');

            return;
        }

        if ($freshRun->status === TelegramGameRun::STATUS_CANCELLED) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже отменена.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Эту сессию уже нельзя запустить.');
    }

    private function handleEmailStep(string $chatId, string $text): void
    {
        $email = mb_strtolower(trim($text));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->bot->sendMessage($chatId, 'Нужен корректный email. Попробуйте ещё раз.');

            return;
        }

        $this->stateStore->storeEmail($chatId, $email);

        $this->bot->sendMessage($chatId, 'Теперь введите пароль от аккаунта сайта.');
    }

    private function handlePasswordStep(string $chatId, string $password, ?string $email, ?string $username): void
    {
        $this->stateStore->clear($chatId);

        if ($email === null || $email === '') {
            $this->bot->sendMessage($chatId, 'Сессия входа истекла. Отправьте /login ещё раз.');

            return;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user instanceof User || ! Hash::check($password, (string) $user->password)) {
            $this->bot->sendMessage($chatId, 'Не удалось выполнить вход. Проверьте данные и начните заново через /login.');

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
            'Telegram успешно подключён к вашему аккаунту WordKeeper.',
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
            'Telegram-аккаунт отключён. Для повторной привязки отправьте /login.',
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
                'Это Telegram-бот WordKeeper.',
                'Он нужен для работы со словарями и тренировками прямо из Telegram.',
                'Чтобы подключить бота к вашему аккаунту сайта, отправьте /login.',
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

    private function extractCallbackChatId(array $callbackQuery): ?string
    {
        $chatId = $callbackQuery['message']['chat']['id'] ?? $callbackQuery['from']['id'] ?? null;

        if ($chatId === null) {
            return null;
        }

        return (string) $chatId;
    }

    private function clearInlineKeyboard(string $chatId, ?int $messageId): void
    {
        if ($messageId === null) {
            return;
        }

        $this->bot->clearInlineKeyboard($chatId, $messageId);
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
