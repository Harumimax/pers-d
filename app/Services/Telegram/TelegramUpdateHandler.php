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
        private readonly TelegramGameRuntimeService $telegramGameRuntimeService,
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
            ->with(['user', 'items'])
            ->find($payload['run_id']);

        if (! $run instanceof TelegramGameRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия не найдена.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramGameRunCallbackData::ACTION_CANCEL) {
            $this->cancelRun($run, $callbackQueryId, $chatId, $messageId);

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramGameRunCallbackData::ACTION_START) {
            $this->startRun($run, $callbackQueryId, $chatId, $messageId);

            return;
        }

        if (($payload['type'] ?? null) === TelegramGameRunCallbackData::ACTION_ANSWER) {
            $this->submitRunAnswer($run, $callbackQueryId, $chatId, $messageId, $payload['item_id'], $payload['option_index']);
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

            if (! in_array($lockedRun->status, [TelegramGameRun::STATUS_AWAITING_START, TelegramGameRun::STATUS_IN_PROGRESS], true)) {
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
        $result = $this->telegramGameRuntimeService->startRun($run);

        if ($result['status'] === 'cancelled') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже отменена.');

            return;
        }

        if ($result['status'] === 'not_startable') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Эту сессию уже нельзя запустить.');

            return;
        }

        if ($result['status'] === 'finished_without_items') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'В этой сессии нет доступных вопросов.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Сессия завершена: для неё не нашлось доступных вопросов.');

            return;
        }

        if ($result['status'] === 'already_started') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже запущена.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия запущена.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if (isset($result['first_item']) && $result['first_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($run->fresh('user'), $result['first_item']);

            return;
        }

        $this->bot->sendMessage($chatId, 'Сессия запущена, но активный вопрос не найден.');
    }

    private function submitRunAnswer(
        TelegramGameRun $run,
        string $callbackQueryId,
        string $chatId,
        ?int $messageId,
        int $itemId,
        int $optionIndex,
    ): void {
        $result = $this->telegramGameRuntimeService->submitAnswer($run, $itemId, $optionIndex);

        if ($result['status'] === 'run_not_in_progress') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия сейчас не активна.');

            return;
        }

        if ($result['status'] === 'item_not_found') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Вопрос не найден.');

            return;
        }

        if ($result['status'] === 'already_answered') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'На этот вопрос уже ответили.');

            return;
        }

        if ($result['status'] === 'wrong_item') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сначала ответьте на текущий вопрос.');

            return;
        }

        if ($result['status'] === 'invalid_option') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Такой вариант ответа недоступен.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Ответ принят.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if ($result['is_correct'] === true) {
            $this->bot->sendMessage($chatId, 'Корректно.');
        } else {
            $correctAnswer = (string) $result['correct_answer'];
            $this->bot->sendMessage($chatId, "Некорректно. Правильный ответ: {$correctAnswer}");
        }

        /** @var TelegramGameRun $freshRun */
        $freshRun = $result['run'];

        if ($result['next_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($freshRun, $result['next_item']);

            return;
        }

        $this->bot->sendMessage($chatId, 'Сессия завершена. Итоговый результат и разбор ошибок подключим следующим этапом.');
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
