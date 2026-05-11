<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramIntervalReviewRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        private readonly TelegramLoginIntentService $telegramLoginIntentService,
        private readonly TelegramAccountLinkService $telegramAccountLinkService,
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
                $this->bot->sendMessage($chatId, 'Р’РІРµРґРёС‚Рµ email РѕС‚ Р°РєРєР°СѓРЅС‚Р° WordKeeper.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Выход', 'Р’С‹С…РѕРґ', '/logout'], true)) {
                $this->stateStore->clear($chatId);
                $this->handleLogout($chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Словари', 'РЎР»РѕРІР°СЂРё'], true)) {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'РЎРЅР°С‡Р°Р»Р° Р°РІС‚РѕСЂРёР·СѓР№С‚РµСЃСЊ РІ Р±РѕС‚Рµ С‡РµСЂРµР· /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->telegramDictionaryMenuService->show($linkedUser, $chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $state = $this->stateStore->get($chatId);

            if ($state === null) {
                $this->bot->sendMessage($chatId, 'РћС‚РїСЂР°РІСЊС‚Рµ /start, С‡С‚РѕР±С‹ СѓРІРёРґРµС‚СЊ РґРѕСЃС‚СѓРїРЅС‹Рµ РєРѕРјР°РЅРґС‹.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_EMAIL) {
                $this->handleEmailStep($chatId, $text, $username);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $this->bot->sendMessage($chatId, 'РћС‚РїСЂР°РІСЊС‚Рµ /start, С‡С‚РѕР±С‹ СѓРІРёРґРµС‚СЊ РґРѕСЃС‚СѓРїРЅС‹Рµ РєРѕРјР°РЅРґС‹.');
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
        $chatId = $this->extractCallbackChatId($callbackQuery);
        $messageId = isset($callbackQuery['message']['message_id']) && is_numeric($callbackQuery['message']['message_id'])
            ? (int) $callbackQuery['message']['message_id']
            : null;

        if ($callbackQueryId === '' || $chatId === null) {
            if ($callbackQueryId !== '') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р”РµР№СЃС‚РІРёРµ РЅРµРґРѕСЃС‚СѓРїРЅРѕ.');
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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р”РµР№СЃС‚РІРёРµ РЅРµРґРѕСЃС‚СѓРїРЅРѕ.');

            return;
        }

        /** @var TelegramGameRun|null $run */
        $run = TelegramGameRun::query()
            ->with(['user', 'items'])
            ->find($gamePayload['run_id']);

        if (! $run instanceof TelegramGameRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ РЅРµ РЅР°Р№РґРµРЅР°.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРЅР°С‡Р°Р»Р° Р°РІС‚РѕСЂРёР·СѓР№С‚РµСЃСЊ РІ Р±РѕС‚Рµ С‡РµСЂРµР· /login.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕС‚РєСЂС‹С‚СЊ СЃР»РѕРІР°СЂСЊ.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎР»РѕРІР°СЂСЊ РЅРµ РЅР°Р№РґРµРЅ.');

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

            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ РѕС‚РјРµРЅРµРЅР°.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'РўРµРєСѓС‰Р°СЏ Telegram-СЃРµСЃСЃРёСЏ РѕС‚РјРµРЅРµРЅР°.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р­С‚Сѓ СЃРµСЃСЃРёСЋ СѓР¶Рµ РЅРµР»СЊР·СЏ РѕС‚РјРµРЅРёС‚СЊ.');
    }

    private function startRun(TelegramGameRun $run, string $callbackQueryId, string $chatId, ?int $messageId): void
    {
        $result = $this->telegramGameRuntimeService->startRun($run);

        if ($result['status'] === 'cancelled') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СѓР¶Рµ РѕС‚РјРµРЅРµРЅР°.');

            return;
        }

        if ($result['status'] === 'not_startable') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р­С‚Сѓ СЃРµСЃСЃРёСЋ СѓР¶Рµ РЅРµР»СЊР·СЏ Р·Р°РїСѓСЃС‚РёС‚СЊ.');

            return;
        }

        if ($result['status'] === 'finished_without_items') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р’ СЌС‚РѕР№ СЃРµСЃСЃРёРё РЅРµС‚ РґРѕСЃС‚СѓРїРЅС‹С… РІРѕРїСЂРѕСЃРѕРІ.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°: РґР»СЏ РЅРµС‘ РЅРµ РЅР°С€Р»РѕСЃСЊ РґРѕСЃС‚СѓРїРЅС‹С… РІРѕРїСЂРѕСЃРѕРІ.');

            return;
        }

        if ($result['status'] === 'already_started') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СѓР¶Рµ Р·Р°РїСѓС‰РµРЅР°.');

            return;
        }

        Log::info('telegram.runtime.run_started', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
        ]);

        $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ Р·Р°РїСѓС‰РµРЅР°.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if (isset($result['first_item']) && $result['first_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($run->fresh('user'), $result['first_item']);

            return;
        }

        $this->bot->sendMessage($chatId, 'РЎРµСЃСЃРёСЏ Р·Р°РїСѓС‰РµРЅР°, РЅРѕ Р°РєС‚РёРІРЅС‹Р№ РІРѕРїСЂРѕСЃ РЅРµ РЅР°Р№РґРµРЅ.');
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
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СЃРµР№С‡Р°СЃ РЅРµ Р°РєС‚РёРІРЅР°.');

            return;
        }

        if ($result['status'] === 'item_not_found') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р’РѕРїСЂРѕСЃ РЅРµ РЅР°Р№РґРµРЅ.');

            return;
        }

        if ($result['status'] === 'already_answered') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РќР° СЌС‚РѕС‚ РІРѕРїСЂРѕСЃ СѓР¶Рµ РѕС‚РІРµС‚РёР»Рё.');

            return;
        }

        if ($result['status'] === 'wrong_item') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРЅР°С‡Р°Р»Р° РѕС‚РІРµС‚СЊС‚Рµ РЅР° С‚РµРєСѓС‰РёР№ РІРѕРїСЂРѕСЃ.');

            return;
        }

        if ($result['status'] === 'invalid_option') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'РўР°РєРѕР№ РІР°СЂРёР°РЅС‚ РѕС‚РІРµС‚Р° РЅРµРґРѕСЃС‚СѓРїРµРЅ.');

            return;
        }

        Log::info('telegram.runtime.answer_accepted', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
            'item_id' => $itemId,
            'option_index' => $optionIndex,
            'is_correct' => $result['is_correct'],
        ]);

        $this->bot->answerCallbackQuery($callbackQueryId, 'РћС‚РІРµС‚ РїСЂРёРЅСЏС‚.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if ($result['is_correct'] === true) {
            $this->bot->sendMessage($chatId, 'РљРѕСЂСЂРµРєС‚РЅРѕ.');
        } else {
            $correctAnswer = (string) $result['correct_answer'];
            $this->bot->sendMessage($chatId, "РќРµРєРѕСЂСЂРµРєС‚РЅРѕ. РџСЂР°РІРёР»СЊРЅС‹Р№ РѕС‚РІРµС‚: {$correctAnswer}");
        }

        /** @var TelegramGameRun $freshRun */
        $freshRun = $result['run'];

        if ($result['next_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($freshRun, $result['next_item']);

            return;
        }

        $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
            ? $result['summary_text']
            : 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°.';

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ РЅРµ РЅР°Р№РґРµРЅР°.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_CANCEL) {
            $result = $this->telegramIntervalReviewRuntimeService->cancelRun($run);

            if ($result['status'] === 'cancelled') {
                Log::info('telegram.interval_review.run_cancelled', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ РѕС‚РјРµРЅРµРЅР°.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'Р­С‚Р° СЃРµСЃСЃРёСЏ РёРЅС‚РµСЂРІР°Р»СЊРЅРѕРіРѕ РїРѕРІС‚РѕСЂРµРЅРёСЏ РѕС‚РјРµРЅРµРЅР°. РЎР»РµРґСѓСЋС‰РёРµ СЃРµСЃСЃРёРё РїР»Р°РЅР° РїСЂРѕРґРѕР»Р¶Р°С‚ СЂР°Р±РѕС‚Р°С‚СЊ РїРѕ СЂР°СЃРїРёСЃР°РЅРёСЋ.');

                return;
            }

            if ($result['status'] === 'already_cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СѓР¶Рµ РѕС‚РјРµРЅРµРЅР°.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р­С‚Сѓ СЃРµСЃСЃРёСЋ СѓР¶Рµ РЅРµР»СЊР·СЏ РѕС‚РјРµРЅРёС‚СЊ.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_START) {
            $result = $this->telegramIntervalReviewRuntimeService->startRun($run);

            if ($result['status'] === 'started') {
                Log::info('telegram.interval_review.run_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ Р·Р°РїСѓС‰РµРЅР°.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->telegramIntervalReviewRuntimeService->sendWordList($result['run']);

                return;
            }

            if ($result['status'] === 'finished_without_items') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р’ СЌС‚РѕР№ СЃРµСЃСЃРёРё РЅРµС‚ РґРѕСЃС‚СѓРїРЅС‹С… СЃР»РѕРІ.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°: РґР»СЏ РЅРµС‘ РЅРµ РЅР°С€Р»РѕСЃСЊ РґРѕСЃС‚СѓРїРЅС‹С… СЃР»РѕРІ.');

                return;
            }

            if ($result['status'] === 'already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СѓР¶Рµ Р·Р°РїСѓС‰РµРЅР°.');

                return;
            }

            if ($result['status'] === 'cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СѓР¶Рµ РѕС‚РјРµРЅРµРЅР°.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р­С‚Сѓ СЃРµСЃСЃРёСЋ СѓР¶Рµ РЅРµР»СЊР·СЏ Р·Р°РїСѓСЃС‚РёС‚СЊ.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_BEGIN_QUIZ) {
            $result = $this->telegramIntervalReviewRuntimeService->beginQuiz($run);

            if ($result['status'] === 'quiz_started') {
                Log::info('telegram.interval_review.quiz_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'РљРІРёР· РЅР°С‡Р°С‚.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            if ($result['status'] === 'finished_without_questions') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->bot->sendMessage($chatId, (string) ($result['summary_text'] ?? 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°.'));

                return;
            }

            if ($result['status'] === 'quiz_already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РљРІРёР· СѓР¶Рµ РЅР°С‡Р°С‚.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЋ СЃРµР№С‡Р°СЃ РЅРµР»СЊР·СЏ РїРµСЂРµРІРµСЃС‚Рё РІ СЂРµР¶РёРј РєРІРёР·Р°.');

            return;
        }

        if (($payload['type'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_ANSWER) {
            $result = $this->telegramIntervalReviewRuntimeService->submitAnswer(
                $run,
                (int) $payload['item_id'],
                (int) $payload['option_index'],
            );

            if ($result['status'] === 'run_not_in_progress') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРµСЃСЃРёСЏ СЃРµР№С‡Р°СЃ РЅРµ Р°РєС‚РёРІРЅР°.');

                return;
            }

            if ($result['status'] === 'item_not_found') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р’РѕРїСЂРѕСЃ РЅРµ РЅР°Р№РґРµРЅ.');

                return;
            }

            if ($result['status'] === 'already_answered') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РќР° СЌС‚РѕС‚ РІРѕРїСЂРѕСЃ СѓР¶Рµ РѕС‚РІРµС‚РёР»Рё.');

                return;
            }

            if ($result['status'] === 'wrong_item') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РЎРЅР°С‡Р°Р»Р° РѕС‚РІРµС‚СЊС‚Рµ РЅР° С‚РµРєСѓС‰РёР№ РІРѕРїСЂРѕСЃ.');

                return;
            }

            if ($result['status'] === 'invalid_option') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'РўР°РєРѕР№ РІР°СЂРёР°РЅС‚ РѕС‚РІРµС‚Р° РЅРµРґРѕСЃС‚СѓРїРµРЅ.');

                return;
            }

            Log::info('telegram.interval_review.answer_accepted', [
                'telegram_interval_review_run_id' => $run->id,
                'user_id' => $run->user_id,
                'item_id' => (int) $payload['item_id'],
                'option_index' => (int) $payload['option_index'],
                'is_correct' => $result['is_correct'],
            ]);

            $this->bot->answerCallbackQuery($callbackQueryId, 'РћС‚РІРµС‚ РїСЂРёРЅСЏС‚.');
            $this->clearInlineKeyboard($chatId, $messageId);

            if (($result['is_correct'] ?? false) === true) {
                $this->bot->sendMessage($chatId, 'РљРѕСЂСЂРµРєС‚РЅРѕ.');
            } else {
                $this->bot->sendMessage($chatId, 'РќРµРєРѕСЂСЂРµРєС‚РЅРѕ. РџСЂР°РІРёР»СЊРЅС‹Р№ РѕС‚РІРµС‚: '.(string) $result['correct_answer']);
            }

            if (($result['next_item'] ?? null) !== null) {
                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
                ? $result['summary_text']
                : 'РЎРµСЃСЃРёСЏ Р·Р°РІРµСЂС€РµРЅР°.';

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

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р”РµР№СЃС‚РІРёРµ РЅРµРґРѕСЃС‚СѓРїРЅРѕ.');
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
                    'Р­С‚Рѕ Telegram-Р±РѕС‚ WordKeeper.',
                    'Р’С‹ СѓР¶Рµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅС‹ Рё РјРѕР¶РµС‚Рµ РїСЂРѕСЃРјР°С‚СЂРёРІР°С‚СЊ СЃРІРѕРё СЃР»РѕРІР°СЂРё РїСЂСЏРјРѕ РёР· Telegram.',
                    'РќР°Р¶РјРёС‚Рµ В«РЎР»РѕРІР°СЂРёВ», С‡С‚РѕР±С‹ РѕС‚РєСЂС‹С‚СЊ СЃРїРёСЃРѕРє РІР°С€РёС… СЃР»РѕРІР°СЂРµР№.',
                ]),
                $this->mainMenuReplyMarkup(),
            );

            return;
        }

        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", [
                'Р­С‚Рѕ Telegram-Р±РѕС‚ WordKeeper.',
                'РћРЅ РЅСѓР¶РµРЅ РґР»СЏ СЂР°Р±РѕС‚С‹ СЃРѕ СЃР»РѕРІР°СЂСЏРјРё Рё С‚СЂРµРЅРёСЂРѕРІРєР°РјРё РїСЂСЏРјРѕ РёР· Telegram.',
                'Р§С‚РѕР±С‹ РїРѕРґРєР»СЋС‡РёС‚СЊ Р±РѕС‚Р° Рє РІР°С€РµРјСѓ Р°РєРєР°СѓРЅС‚Сѓ СЃР°Р№С‚Р°, РѕС‚РїСЂР°РІСЊС‚Рµ /login.',
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
                    [['text' => 'РЎР»РѕРІР°СЂРё']],
                    [['text' => 'Р’С‹С…РѕРґ']],
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
