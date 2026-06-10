<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramIntervalReviewRun;
use App\Models\User;
use App\Models\UserDictionary;
use App\Services\Dictionaries\SaveDictionaryWordService;
use App\Services\Dictionaries\UserDictionaryWordSearchService;
use App\Services\Translation\TranslationServiceInterface;
use App\Support\DictionaryLanguageCode;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramUpdateHandler
{
    private const ADD_WORD_MAX_LENGTH = 50;
    private const TARGET_LANGUAGE = 'ru';
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramAuthStateStore $stateStore,
        private readonly TelegramProcessedUpdateService $telegramProcessedUpdateService,
        private readonly TelegramGameRunCallbackData $telegramGameRunCallbackData,
        private readonly TelegramGameRuntimeService $telegramGameRuntimeService,
        private readonly TelegramIntervalReviewRunCallbackData $telegramIntervalReviewRunCallbackData,
        private readonly TelegramIntervalReviewRuntimeService $telegramIntervalReviewRuntimeService,
        private readonly TelegramDictionaryCallbackData $telegramDictionaryCallbackData,
        private readonly TelegramAddWordCallbackData $telegramAddWordCallbackData,
        private readonly TelegramDictionaryMenuService $telegramDictionaryMenuService,
        private readonly TelegramDictionaryViewService $telegramDictionaryViewService,
        private readonly TelegramLoginIntentService $telegramLoginIntentService,
        private readonly TelegramAccountLinkService $telegramAccountLinkService,
        private readonly UserDictionaryWordSearchService $userDictionaryWordSearchService,
        private readonly SaveDictionaryWordService $saveDictionaryWordService,
        private readonly TranslationServiceInterface $translationService,
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

            if (in_array($text, ['Выход', '/logout'], true)) {
                $this->stateStore->clear($chatId);
                $this->handleLogout($chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Словари'], true)) {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->telegramDictionaryMenuService->show($linkedUser, $chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Поиск слов'], true)) {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->stateStore->startDictionaryWordSearch($chatId);
                $this->bot->sendMessage(
                    $chatId,
                    implode("\n", [
                        'Введите слово или часть слова для поиска.',
                        'Поиск будет осуществлён по вашим словарям по словам и их переводам.',
                    ]),
                    $this->mainMenuReplyMarkup(),
                );
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Добавить слово'], true)) {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->showAddWordDictionaryPicker($chatId, $linkedUser);
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
                $this->handleEmailStep($chatId, $text, $username);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_DICTIONARY_SEARCH_QUERY) {
                $this->handleDictionaryWordSearchStep($chatId, $text, $linkedUser);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TEXT) {
                $this->handleAddWordTextStep($chatId, $text, $linkedUser, $state);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $this->bot->sendMessage($chatId, 'Отправьте /start, чтобы увидеть доступные команды.');
            $this->telegramProcessedUpdateService->markProcessed($processedUpdate);
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
        $addWordPayload = $this->telegramAddWordCallbackData->parse($callbackData);
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

        if (is_array($addWordPayload)) {
            $this->handleAddWordCallbackQuery($callbackQueryId, $chatId, $messageId, $addWordPayload);

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

    private function handleEmailStep(string $chatId, string $text, ?string $username): void
    {
        $email = mb_strtolower(trim($text));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->bot->sendMessage($chatId, 'Нужен корректный email. Попробуйте ещё раз.');

            return;
        }

        $this->stateStore->clear($chatId);

        $result = $this->telegramLoginIntentService->startForEmail($chatId, $username, $email);

        if ($result['status'] === 'user_not_found') {
            $this->bot->sendMessage($chatId, 'Аккаунт с таким email не найден. Зарегистрируйтесь на сайте: '.route('register'));

            return;
        }

        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", [
                'Аккаунт найден. Для подтверждения авторизуйтесь на сайте.',
                $result['url'],
            ]),
        );
    }

    private function handleDictionaryWordSearchStep(string $chatId, string $text, ?User $linkedUser): void
    {
        if (! $linkedUser instanceof User) {
            $this->stateStore->clear($chatId);
            $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');

            return;
        }

        $query = trim($text);

        if ($query === '') {
            $this->bot->sendMessage($chatId, 'Нужно ввести слово или часть слова для поиска.');

            return;
        }

        $results = $this->userDictionaryWordSearchService->search($linkedUser, $query);
        $this->stateStore->clear($chatId);

        if ($results->isEmpty()) {
            $this->bot->sendMessage(
                $chatId,
                implode("\n", [
                    'Результаты поиска:',
                    'Таких слов не найдено в ваших словарях.',
                ]),
                $this->mainMenuReplyMarkup(),
            );

            return;
        }

        $resultBlocks = $results
            ->values()
            ->map(function (object $result, int $index): string {
                $lines = [
                    ($index + 1).'. '.$result->word.' - '.$result->translation,
                ];

                if (is_string($result->comment) && trim($result->comment) !== '') {
                    $lines[] = trim($result->comment);
                }

                $lines[] = $result->dictionary_name;

                return implode("\n", $lines);
            })
            ->all();

        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", array_merge(['Результаты поиска:'], $resultBlocks)),
            $this->mainMenuReplyMarkup(),
        );
    }

    /**
     * @param  array<string, int|string>  $payload
     */
    private function handleAddWordCallbackQuery(string $callbackQueryId, string $chatId, ?int $messageId, array $payload): void
    {
        $linkedUser = $this->findLinkedUserByChatId($chatId);

        if (! $linkedUser instanceof User) {
            $this->stateStore->clear($chatId);
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сначала авторизуйтесь через /login.');

            return;
        }

        $action = (string) ($payload['action'] ?? '');
        $value = $payload['value'] ?? null;

        if ($action === TelegramAddWordCallbackData::ACTION_CANCEL) {
            $this->stateStore->clear($chatId);
            $this->bot->answerCallbackQuery($callbackQueryId, 'Добавление слова отменено.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Добавление слова отменено.', $this->mainMenuReplyMarkup());

            return;
        }

        if ($action === TelegramAddWordCallbackData::ACTION_DICTIONARY) {
            $dictionary = $this->ownedDictionary($linkedUser, (int) $value);

            if (! $dictionary instanceof UserDictionary) {
                $this->stateStore->clear($chatId);
                $this->bot->answerCallbackQuery($callbackQueryId, 'Словарь не найден.');

                return;
            }

            $this->stateStore->startAddWord($chatId, $dictionary->id, $dictionary->name, $dictionary->language);
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Введите слово для перевода. Не более 50 символов.');

            return;
        }

        $state = $this->stateStore->get($chatId);

        if ($state === null) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сценарий истёк. Нажмите «Добавить слово» ещё раз.');

            return;
        }

        if ($action === TelegramAddWordCallbackData::ACTION_TRANSLATION) {
            if ($state['step'] !== TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TRANSLATION) {
                $this->stateStore->clear($chatId);
                $this->bot->answerCallbackQuery($callbackQueryId, 'Сценарий истёк. Нажмите «Добавить слово» ещё раз.');

                return;
            }

            $suggestions = $state['translation_options'] ?? [];
            $index = (int) $value;

            if (! isset($suggestions[$index]['text']) || trim((string) $suggestions[$index]['text']) === '') {
                $this->stateStore->clear($chatId);
                $this->bot->answerCallbackQuery($callbackQueryId, 'Вариант перевода недоступен.');

                return;
            }

            $selectedTranslation = trim((string) $suggestions[$index]['text']);
            $this->stateStore->storeSelectedAddWordTranslation($chatId, $selectedTranslation);
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->showAddWordPartOfSpeechPicker($chatId);

            return;
        }

        if ($action !== TelegramAddWordCallbackData::ACTION_PART_OF_SPEECH) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Действие недоступно.');

            return;
        }

        if ($state['step'] !== TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_PART_OF_SPEECH) {
            $this->stateStore->clear($chatId);
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сценарий истёк. Нажмите «Добавить слово» ещё раз.');

            return;
        }

        $partOfSpeech = (string) $value;

        if (! in_array($partOfSpeech, PartOfSpeechCatalog::values(), true)) {
            $this->stateStore->clear($chatId);
            $this->bot->answerCallbackQuery($callbackQueryId, 'Часть речи недоступна.');

            return;
        }

        $dictionaryId = (int) ($state['dictionary_id'] ?? 0);
        $word = trim((string) ($state['word'] ?? ''));
        $translation = trim((string) ($state['selected_translation'] ?? ''));
        $dictionary = $this->ownedDictionary($linkedUser, $dictionaryId);

        if (! $dictionary instanceof UserDictionary || $word === '' || $translation === '') {
            $this->stateStore->clear($chatId);
            $this->bot->answerCallbackQuery($callbackQueryId, 'Сценарий истёк. Нажмите «Добавить слово» ещё раз.');

            return;
        }

        $this->saveDictionaryWordService->save($dictionary, $word, $translation, $partOfSpeech);
        $this->stateStore->clear($chatId);
        $this->bot->answerCallbackQuery($callbackQueryId, 'Слово сохранено.');
        $this->clearInlineKeyboard($chatId, $messageId);

        $partOfSpeechLabel = PartOfSpeechCatalog::dictionaryFormLabels()[$partOfSpeech] ?? $partOfSpeech;
        $this->bot->sendMessage(
            $chatId,
            "Слово {$word} ({$partOfSpeechLabel}) успешно сохранено в {$dictionary->name}",
            $this->mainMenuReplyMarkup(),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function handleAddWordTextStep(string $chatId, string $text, ?User $linkedUser, array $state): void
    {
        if (! $linkedUser instanceof User) {
            $this->stateStore->clear($chatId);
            $this->bot->sendMessage($chatId, 'Сначала авторизуйтесь в боте через /login.');

            return;
        }

        $dictionary = $this->ownedDictionary($linkedUser, (int) ($state['dictionary_id'] ?? 0));

        if (! $dictionary instanceof UserDictionary) {
            $this->stateStore->clear($chatId);
            $this->bot->sendMessage($chatId, 'Выбранный словарь больше недоступен. Нажмите «Добавить слово» ещё раз.');

            return;
        }

        $word = trim($text);

        if ($word === '') {
            $this->bot->sendMessage($chatId, 'Введите слово для перевода. Не более 50 символов.');

            return;
        }

        if (mb_strlen($word) > self::ADD_WORD_MAX_LENGTH) {
            $this->bot->sendMessage($chatId, 'Слово не должно превышать 50 символов.');

            return;
        }

        $sourceLanguage = $this->sourceLanguageCode($dictionary);

        if ($sourceLanguage === null) {
            $this->bot->sendMessage($chatId, 'Для этого словаря автоматический перевод пока недоступен.');

            return;
        }

        try {
            $suggestions = array_slice(
                $this->translationService->translate($word, $sourceLanguage, self::TARGET_LANGUAGE)->toArray(),
                0,
                6,
            );
        } catch (ConnectionException|RequestException) {
            $this->bot->sendMessage($chatId, 'Не удалось получить варианты перевода. Попробуйте другое слово.');

            return;
        }

        if ($suggestions === []) {
            $this->bot->sendMessage($chatId, 'Не удалось получить варианты перевода. Попробуйте другое слово.');

            return;
        }

        $this->stateStore->storeAddWordTranslations($chatId, $word, $suggestions);
        $this->showAddWordTranslationPicker($chatId, $suggestions);
    }

    private function handleLogout(string $chatId): void
    {
        $this->telegramAccountLinkService->unlinkByChatId($chatId);

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

    private function showAddWordDictionaryPicker(string $chatId, User $user): void
    {
        $dictionaries = UserDictionary::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get(['id', 'name', 'language']);

        if ($dictionaries->isEmpty()) {
            $this->bot->sendMessage($chatId, 'У вас пока нет словарей. Создайте словарь на сайте и возвращайтесь сюда.');

            return;
        }

        $lines = ['Выберите словарь, в который надо добавить слово:'];

        foreach ($dictionaries as $index => $dictionary) {
            $languageSuffix = filled($dictionary->language) ? " ({$dictionary->language})" : '';
            $lines[] = ($index + 1).'. '.$dictionary->name.$languageSuffix;
        }

        $keyboard = $dictionaries
            ->values()
            ->map(fn (UserDictionary $dictionary, int $index): array => [
                'text' => (string) ($index + 1),
                'callback_data' => $this->telegramAddWordCallbackData->makeDictionary($dictionary->id),
            ])
            ->chunk(4)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $this->bot->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ]);
    }

    /**
     * @param  array<int, array{text:string,label:string}>  $suggestions
     */
    private function showAddWordTranslationPicker(string $chatId, array $suggestions): void
    {
        $lines = ['Выберите вариант перевода:'];

        foreach ($suggestions as $index => $suggestion) {
            $lines[] = sprintf(
                '%d. %s (%s)',
                $index + 1,
                $suggestion['text'],
                $suggestion['label']
            );
        }

        $keyboard = collect($suggestions)
            ->values()
            ->map(fn (array $suggestion, int $index): array => [
                'text' => (string) ($index + 1),
                'callback_data' => $this->telegramAddWordCallbackData->makeTranslation($index),
            ])
            ->chunk(4)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $keyboard[] = [[
            'text' => 'Отмена',
            'callback_data' => $this->telegramAddWordCallbackData->makeCancel(),
        ]];

        $this->bot->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ]);
    }

    private function showAddWordPartOfSpeechPicker(string $chatId): void
    {
        $labels = PartOfSpeechCatalog::dictionaryFormLabels();
        $lines = ['Выберите часть речи:'];
        $entries = [];

        foreach (array_values($labels) as $index => $label) {
            $lines[] = ($index + 1).'. '.$label;
        }

        foreach (array_keys($labels) as $index => $value) {
            $entries[] = [
                'text' => (string) ($index + 1),
                'callback_data' => $this->telegramAddWordCallbackData->makePartOfSpeech($value),
            ];
        }

        $keyboard = collect($entries)
            ->chunk(4)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $keyboard[] = [[
            'text' => 'Отмена',
            'callback_data' => $this->telegramAddWordCallbackData->makeCancel(),
        ]];

        $this->bot->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ]);
    }

    private function ownedDictionary(User $user, int $dictionaryId): ?UserDictionary
    {
        if ($dictionaryId <= 0) {
            return null;
        }

        return UserDictionary::query()
            ->where('user_id', $user->id)
            ->whereKey($dictionaryId)
            ->first();
    }

    private function sourceLanguageCode(UserDictionary $dictionary): ?string
    {
        return DictionaryLanguageCode::fromDictionaryLanguage($dictionary->language);
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
                    [['text' => 'Поиск слов']],
                    [['text' => 'Добавить слово']],
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
