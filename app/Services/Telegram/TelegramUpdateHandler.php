<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramIntervalReviewRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramAuthStateStore $stateStore,
        private readonly TelegramProcessedUpdateService $telegramProcessedUpdateService,
        private readonly TelegramGameRunCallbackData $telegramGameRunCallbackData,
        private readonly TelegramGameRuntimeService $telegramGameRuntimeService,
        private readonly TelegramIntervalReviewRunCallbackData $telegramIntervalReviewRunCallbackData,
        private readonly TelegramIntervalReviewRuntimeService $telegramIntervalReviewRuntimeService,
        private readonly TelegramDictionaryCallbackData $telegramDictionaryCallbackData,
        private readonly TelegramDictionaryMenuService $telegramDictionaryMenuService,
        private readonly TelegramDictionaryViewService $telegramDictionaryViewService,
    ) {
    }

    public function handle(array $update): void
    {
        $processedUpdate = $this->telegramProcessedUpdateService->claim($update);

        if ($processedUpdate === null) {
            Log::info('telegram.webhook.duplicate_update_skipped', [
                'telegram_update_id' => $update['update_id'] ?? null,
                'callback_query_id' => data_get($update, 'callback_query.id'),
            ]);

            return;
        }

        try {
            $callbackQuery = $update['callback_query'] ?? null;

            if (is_array($callbackQuery)) {
                $this->handleCallbackQuery($callbackQuery);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $message = $update['message'] ?? null;

            if (! is_array($message)) {
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $chatId = $this->extractChatId($message);
            $text = trim((string) ($message['text'] ?? ''));

            if ($chatId === null || $text === '') {
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $username = $this->sanitizeTelegramUsername($message['from']['username'] ?? null);
            $linkedUser = $this->findLinkedUserByChatId($chatId);

            if ($text === '/start') {
                $this->stateStore->clear($chatId);
                $this->sendStartMessage($chatId, $linkedUser);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($text === '/login') {
                $this->stateStore->start($chatId);
                $this->bot->sendMessage($chatId, 'Введите email от аккаунта WordKeeper.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($text === 'Выход' || $text === '/logout') {
                $this->stateStore->clear($chatId);
                $this->handleLogout($chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($text === 'Словари') {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->telegramDictionaryMenuService->show($linkedUser, $chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $state = $this->stateStore->get($chatId);

            if ($state === null) {
                $this->bot->sendMessage($chatId, 'Отправьте /start, чтобы увидеть доступные команды.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_EMAIL) {
                $this->handleEmailStep($chatId, $text);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_PASSWORD) {
                $this->handlePasswordStep($chatId, $text, $state['email'], $username);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);
            }
        } catch (Throwable $exception) {
            $this->telegramProcessedUpdateService->markFailed($processedUpdate, $exception->getMessage());

            Log::error('telegram.webhook.update_failed', [
                'telegram_update_id' => $update['update_id'] ?? null,
                'callback_query_id' => data_get($update, 'callback_query.id'),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryId = trim((string) ($callbackQuery['id'] ?? ''));
        $callbackData = trim((string) ($callbackQuery['data'] ?? ''));
        $intervalPayload = $this->telegramIntervalReviewRunCallbackData->parse($callbackData);
        $gamePayload = $this->telegramGameRunCallbackData->parse($callbackData);
        $dictionaryPayload = $this->telegramDictionaryCallbackData->parse($callbackData);
        $chatId = $this->extractCallbackChatId($callbackQuery);
        $messageId = isset($callbackQuery['message']['message_id']) && is_numeric($callbackQuery['message']['message_id'])
            ? (int) $callbackQuery['message']['message_id']
            : null;

        if ($callbackQueryId === '' || $chatId === null) {
            if ($callbackQueryId !== '') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Действие недоступно.');
            }

            return;
        }

        if (is_array($dictionaryPayload)) {
            $this->handleDictionaryCallbackQuery($callbackQueryId, $chatId, $messageId, $dictionaryPayload);

            return;
        }

        if (is_array($intervalPayload)) {
            $this->handleIntervalReviewCallbackQuery($callbackQueryId, $chatId, $messageId, $intervalPayload);

            return;
        }

        if ($gamePayload === null) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Действие недоступно.');

            return;
        }

        /** @var TelegramGameRun|null $run */
        $run = TelegramGameRun::query()
            ->with(['user', 'items'])
            ->find($gamePayload['run_id']);

        if (! $run instanceof TelegramGameRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия не найдена.');

            return;
        }

        if (($gamePayload['type'] ?? null) === 'run_action' && ($gamePayload['action'] ?? null) === TelegramGameRunCallbackData::ACTION_CANCEL) {
            $this->cancelRun($run, $callbackQueryId, $chatId, $messageId);

            return;
        }

        if (($gamePayload['type'] ?? null) === 'run_action' && ($gamePayload['action'] ?? null) === TelegramGameRunCallbackData::ACTION_START) {
            $this->startRun($run, $callbackQueryId, $chatId, $messageId);

            return;
        }

        if (($gamePayload['type'] ?? null) === TelegramGameRunCallbackData::ACTION_ANSWER) {
            $this->submitRunAnswer($run, $callbackQueryId, $chatId, $messageId, $gamePayload['item_id'], $gamePayload['option_index']);
        }
    }

    /**
     * @param  array<string, int|string>  $payload
     */
    private function handleDictionaryCallbackQuery(string $callbackQueryId, string $chatId, ?int $messageId, array $payload): void
    {
        $linkedUser = $this->findLinkedUserByChatId($chatId);

        if (! $linkedUser instanceof User) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сначала авторизуйтесь в боте через /login.');

            return;
        }

        $action = $payload['action'] ?? null;

        if ($action === TelegramDictionaryCallbackData::ACTION_NOOP) {
            $this->bot->answerCallbackQuery($callbackQueryId);

            return;
        }

        if (in_array($action, [TelegramDictionaryCallbackData::ACTION_LIST, TelegramDictionaryCallbackData::ACTION_BACK], true)) {
            $this->bot->answerCallbackQuery($callbackQueryId);

            if ($messageId !== null) {
                $this->telegramDictionaryMenuService->show($linkedUser, $chatId, $messageId);
            } else {
                $this->telegramDictionaryMenuService->show($linkedUser, $chatId);
            }

            return;
        }

        if ($messageId === null) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Не удалось открыть словарь.');

            return;
        }

        $result = $this->telegramDictionaryViewService->show(
            $linkedUser,
            (int) $payload['dictionary_id'],
            (int) $payload['page'],
            $chatId,
            $messageId,
        );

        if ($result['status'] === 'not_found') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Словарь не найден.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId);
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
                'last_interaction_at' => now(),
                'last_error_code' => null,
                'last_error_message' => null,
                'last_error_at' => null,
            ])->save();

            return $lockedRun;
        });

        if ($freshRun->status === TelegramGameRun::STATUS_CANCELLED) {
            Log::info('telegram.runtime.run_cancelled', [
                'telegram_game_run_id' => $freshRun->id,
                'user_id' => $freshRun->user_id,
            ]);

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

        Log::info('telegram.runtime.run_started', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
        ]);

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

        Log::info('telegram.runtime.answer_accepted', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
            'item_id' => $itemId,
            'option_index' => $optionIndex,
            'is_correct' => $result['is_correct'],
        ]);

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

        $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
            ? $result['summary_text']
            : 'Сессия завершена.';

        Log::info('telegram.runtime.run_finished', [
            'telegram_game_run_id' => $freshRun->id,
            'user_id' => $freshRun->user_id,
            'correct_answers' => $freshRun->correct_answers,
            'incorrect_answers' => $freshRun->incorrect_answers,
        ]);

        $this->bot->sendMessage($chatId, $summaryText);
    }

    /**
     * @param  array<string, int|string>  $payload
     */
    private function handleIntervalReviewCallbackQuery(string $callbackQueryId, string $chatId, ?int $messageId, array $payload): void
    {
        /** @var TelegramIntervalReviewRun|null $run */
        $run = TelegramIntervalReviewRun::query()
            ->with(['user', 'session', 'items'])
            ->find($payload['run_id']);

        if (! $run instanceof TelegramIntervalReviewRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия не найдена.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_CANCEL) {
            $result = $this->telegramIntervalReviewRuntimeService->cancelRun($run);

            if ($result['status'] === 'cancelled') {
                Log::info('telegram.interval_review.run_cancelled', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия отменена.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'Эта сессия интервального повторения отменена. Следующие сессии плана продолжат работать по расписанию.');

                return;
            }

            if ($result['status'] === 'already_cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже отменена.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Эту сессию уже нельзя отменить.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_START) {
            $result = $this->telegramIntervalReviewRuntimeService->startRun($run);

            if ($result['status'] === 'started') {
                Log::info('telegram.interval_review.run_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия запущена.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->telegramIntervalReviewRuntimeService->sendWordList($result['run']);

                return;
            }

            if ($result['status'] === 'finished_without_items') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'В этой сессии нет доступных слов.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'Сессия завершена: для неё не нашлось доступных слов.');

                return;
            }

            if ($result['status'] === 'already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже запущена.');

                return;
            }

            if ($result['status'] === 'cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия уже отменена.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Эту сессию уже нельзя запустить.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_BEGIN_QUIZ) {
            $result = $this->telegramIntervalReviewRuntimeService->beginQuiz($run);

            if ($result['status'] === 'quiz_started') {
                Log::info('telegram.interval_review.quiz_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Квиз начат.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            if ($result['status'] === 'finished_without_questions') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Сессия завершена.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->bot->sendMessage($chatId, (string) ($result['summary_text'] ?? 'Сессия завершена.'));

                return;
            }

            if ($result['status'] === 'quiz_already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Квиз уже начат.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Сессию сейчас нельзя перевести в режим квиза.');

            return;
        }

        if (($payload['type'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_ANSWER) {
            $result = $this->telegramIntervalReviewRuntimeService->submitAnswer(
                $run,
                (int) $payload['item_id'],
                (int) $payload['option_index'],
            );

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

            Log::info('telegram.interval_review.answer_accepted', [
                'telegram_interval_review_run_id' => $run->id,
                'user_id' => $run->user_id,
                'item_id' => (int) $payload['item_id'],
                'option_index' => (int) $payload['option_index'],
                'is_correct' => $result['is_correct'],
            ]);

            $this->bot->answerCallbackQuery($callbackQueryId, 'Ответ принят.');
            $this->clearInlineKeyboard($chatId, $messageId);

            if (($result['is_correct'] ?? false) === true) {
                $this->bot->sendMessage($chatId, 'Корректно.');
            } else {
                $this->bot->sendMessage($chatId, 'Некорректно. Правильный ответ: '.(string) $result['correct_answer']);
            }

            if (($result['next_item'] ?? null) !== null) {
                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
                ? $result['summary_text']
                : 'Сессия завершена.';

            Log::info('telegram.interval_review.run_finished', [
                'telegram_interval_review_run_id' => $run->id,
                'user_id' => $run->user_id,
            ]);

            $this->bot->sendMessage($chatId, $summaryText);

            if (is_string($result['completion_message'] ?? null) && $result['completion_message'] !== '') {
                $this->bot->sendMessage($chatId, $result['completion_message']);
            }

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Действие недоступно.');
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
            $this->mainMenuReplyMarkup(),
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

    private function sendStartMessage(string $chatId, ?User $linkedUser = null): void
    {
        if ($linkedUser instanceof User) {
            $this->bot->sendMessage(
                $chatId,
                implode("\n\n", [
                    'Это Telegram-бот WordKeeper.',
                    'Вы уже авторизованы и можете просматривать свои словари прямо из Telegram.',
                    'Нажмите «Словари», чтобы открыть список ваших словарей.',
                ]),
                $this->mainMenuReplyMarkup(),
            );

            return;
        }

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

    /**
     * @return array<string, mixed>
     */
    private function mainMenuReplyMarkup(): array
    {
        return [
            'reply_markup' => [
                'keyboard' => [
                    [['text' => 'Словари']],
                    [['text' => 'Выход']],
                ],
                'resize_keyboard' => true,
            ],
        ];
    }

    private function findLinkedUserByChatId(string $chatId): ?User
    {
        return User::query()
            ->where('tg_chat_id', $chatId)
            ->first();
    }
}
