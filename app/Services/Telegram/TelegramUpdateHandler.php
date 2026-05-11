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
                $this->bot->sendMessage($chatId, 'Введите email от аккаунта WordKeeper.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Р вЂ™РЎвЂ№РЎвЂ¦Р С•Р Т‘', 'Р В РІР‚в„ўР РЋРІР‚в„–Р РЋРІР‚В¦Р В РЎвЂўР В РўвЂ', '/logout'], true)) {
                $this->stateStore->clear($chatId);
                $this->handleLogout($chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if (in_array($text, ['Р РЋР В»Р С•Р Р†Р В°РЎР‚Р С‘', 'Р В Р Р‹Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂ'], true)) {
                if (! $linkedUser instanceof User) {
                    $this->bot->sendMessage($chatId, 'Р В Р Р‹Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р В Р’В»Р В Р’В° Р В Р’В°Р В Р вЂ Р РЋРІР‚С™Р В РЎвЂўР РЋР вЂљР В РЎвЂР В Р’В·Р РЋРЎвЂњР В РІвЂћвЂ“Р РЋРІР‚С™Р В Р’ВµР РЋР С“Р РЋР Р‰ Р В Р вЂ  Р В Р’В±Р В РЎвЂўР РЋРІР‚С™Р В Р’Вµ Р РЋРІР‚РЋР В Р’ВµР РЋР вЂљР В Р’ВµР В Р’В· /login.');
                    $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                    return;
                }

                $this->telegramDictionaryMenuService->show($linkedUser, $chatId);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $state = $this->stateStore->get($chatId);

            if ($state === null) {
                $this->bot->sendMessage($chatId, 'Р В РЎвЂєР РЋРІР‚С™Р В РЎвЂ”Р РЋР вЂљР В Р’В°Р В Р вЂ Р РЋР Р‰Р РЋРІР‚С™Р В Р’Вµ /start, Р РЋРІР‚РЋР РЋРІР‚С™Р В РЎвЂўР В Р’В±Р РЋРІР‚в„– Р РЋРЎвЂњР В Р вЂ Р В РЎвЂР В РўвЂР В Р’ВµР РЋРІР‚С™Р РЋР Р‰ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р В Р’Вµ Р В РЎвЂќР В РЎвЂўР В РЎВР В Р’В°Р В Р вЂ¦Р В РўвЂР РЋРІР‚в„–.');
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            if ($state['step'] === TelegramAuthStateStore::STEP_AWAITING_EMAIL) {
                $this->handleEmailStep($chatId, $text, $username);
                $this->telegramProcessedUpdateService->markProcessed($processedUpdate);

                return;
            }

            $this->bot->sendMessage($chatId, 'Р В РЎвЂєР РЋРІР‚С™Р В РЎвЂ”Р РЋР вЂљР В Р’В°Р В Р вЂ Р РЋР Р‰Р РЋРІР‚С™Р В Р’Вµ /start, Р РЋРІР‚РЋР РЋРІР‚С™Р В РЎвЂўР В Р’В±Р РЋРІР‚в„– Р РЋРЎвЂњР В Р вЂ Р В РЎвЂР В РўвЂР В Р’ВµР РЋРІР‚С™Р РЋР Р‰ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р В Р’Вµ Р В РЎвЂќР В РЎвЂўР В РЎВР В Р’В°Р В Р вЂ¦Р В РўвЂР РЋРІР‚в„–.');
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
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚СњР В Р’ВµР В РІвЂћвЂ“Р РЋР С“Р РЋРІР‚С™Р В Р вЂ Р В РЎвЂР В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р В РЎвЂў.');
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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚СњР В Р’ВµР В РІвЂћвЂ“Р РЋР С“Р РЋРІР‚С™Р В Р вЂ Р В РЎвЂР В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р В РЎвЂў.');

            return;
        }

        /** @var TelegramGameRun|null $run */
        $run = TelegramGameRun::query()
            ->with(['user', 'items'])
            ->find($gamePayload['run_id']);

        if (! $run instanceof TelegramGameRun || (string) $run->user->tg_chat_id !== $chatId) {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦Р В Р’В°.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р В Р’В»Р В Р’В° Р В Р’В°Р В Р вЂ Р РЋРІР‚С™Р В РЎвЂўР РЋР вЂљР В РЎвЂР В Р’В·Р РЋРЎвЂњР В РІвЂћвЂ“Р РЋРІР‚С™Р В Р’ВµР РЋР С“Р РЋР Р‰ Р В Р вЂ  Р В Р’В±Р В РЎвЂўР РЋРІР‚С™Р В Р’Вµ Р РЋРІР‚РЋР В Р’ВµР РЋР вЂљР В Р’ВµР В Р’В· /login.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎСљР В Р’Вµ Р РЋРЎвЂњР В РўвЂР В Р’В°Р В Р’В»Р В РЎвЂўР РЋР С“Р РЋР Р‰ Р В РЎвЂўР РЋРІР‚С™Р В РЎвЂќР РЋР вЂљР РЋРІР‚в„–Р РЋРІР‚С™Р РЋР Р‰ Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР РЋР Р‰.');

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР РЋР Р‰ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦.');

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

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Р В РЎС›Р В Р’ВµР В РЎвЂќР РЋРЎвЂњР РЋРІР‚В°Р В Р’В°Р РЋР РЏ Telegram-Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р’В­Р РЋРІР‚С™Р РЋРЎвЂњ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР вЂ№ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В Р’В»Р РЋР Р‰Р В Р’В·Р РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В РЎвЂР РЋРІР‚С™Р РЋР Р‰.');
    }

    private function startRun(TelegramGameRun $run, string $callbackQueryId, string $chatId, ?int $messageId): void
    {
        $result = $this->telegramGameRuntimeService->startRun($run);

        if ($result['status'] === 'cancelled') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

            return;
        }

        if ($result['status'] === 'not_startable') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р’В­Р РЋРІР‚С™Р РЋРЎвЂњ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР вЂ№ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В Р’В»Р РЋР Р‰Р В Р’В·Р РЋР РЏ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋР С“Р РЋРІР‚С™Р В РЎвЂР РЋРІР‚С™Р РЋР Р‰.');

            return;
        }

        if ($result['status'] === 'finished_without_items') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚в„ў Р РЋР РЉР РЋРІР‚С™Р В РЎвЂўР В РІвЂћвЂ“ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР В РЎвЂ Р В Р вЂ¦Р В Р’ВµР РЋРІР‚С™ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р РЋРІР‚В¦ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“Р В РЎвЂўР В Р вЂ .');
            $this->clearInlineKeyboard($chatId, $messageId);
            $this->bot->sendMessage($chatId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°: Р В РўвЂР В Р’В»Р РЋР РЏ Р В Р вЂ¦Р В Р’ВµР РЋРІР‚В Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р РЋРІвЂљВ¬Р В Р’В»Р В РЎвЂўР РЋР С“Р РЋР Р‰ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р РЋРІР‚В¦ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“Р В РЎвЂўР В Р вЂ .');

            return;
        }

        if ($result['status'] === 'already_started') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋРІР‚В°Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

            return;
        }

        Log::info('telegram.runtime.run_started', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
        ]);

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋРІР‚В°Р В Р’ВµР В Р вЂ¦Р В Р’В°.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if (isset($result['first_item']) && $result['first_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($run->fresh('user'), $result['first_item']);

            return;
        }

        $this->bot->sendMessage($chatId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋРІР‚В°Р В Р’ВµР В Р вЂ¦Р В Р’В°, Р В Р вЂ¦Р В РЎвЂў Р В Р’В°Р В РЎвЂќР РЋРІР‚С™Р В РЎвЂР В Р вЂ Р В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦.');
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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋР С“Р В Р’ВµР В РІвЂћвЂ“Р РЋРІР‚РЋР В Р’В°Р РЋР С“ Р В Р вЂ¦Р В Р’Вµ Р В Р’В°Р В РЎвЂќР РЋРІР‚С™Р В РЎвЂР В Р вЂ Р В Р вЂ¦Р В Р’В°.');

            return;
        }

        if ($result['status'] === 'item_not_found') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚в„ўР В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦.');

            return;
        }

        if ($result['status'] === 'already_answered') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎСљР В Р’В° Р РЋР РЉР РЋРІР‚С™Р В РЎвЂўР РЋРІР‚С™ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р В РЎвЂР В Р’В»Р В РЎвЂ.');

            return;
        }

        if ($result['status'] === 'wrong_item') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р В Р’В»Р В Р’В° Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р РЋР Р‰Р РЋРІР‚С™Р В Р’Вµ Р В Р вЂ¦Р В Р’В° Р РЋРІР‚С™Р В Р’ВµР В РЎвЂќР РЋРЎвЂњР РЋРІР‚В°Р В РЎвЂР В РІвЂћвЂ“ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“.');

            return;
        }

        if ($result['status'] === 'invalid_option') {
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎС›Р В Р’В°Р В РЎвЂќР В РЎвЂўР В РІвЂћвЂ“ Р В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂР В Р’В°Р В Р вЂ¦Р РЋРІР‚С™ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р В Р’В° Р В Р вЂ¦Р В Р’ВµР В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р’ВµР В Р вЂ¦.');

            return;
        }

        Log::info('telegram.runtime.answer_accepted', [
            'telegram_game_run_id' => $run->id,
            'user_id' => $run->user_id,
            'item_id' => $itemId,
            'option_index' => $optionIndex,
            'is_correct' => $result['is_correct'],
        ]);

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎвЂєР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™ Р В РЎвЂ”Р РЋР вЂљР В РЎвЂР В Р вЂ¦Р РЋР РЏР РЋРІР‚С™.');
        $this->clearInlineKeyboard($chatId, $messageId);

        if ($result['is_correct'] === true) {
            $this->bot->sendMessage($chatId, 'Р В РЎв„ўР В РЎвЂўР РЋР вЂљР РЋР вЂљР В Р’ВµР В РЎвЂќР РЋРІР‚С™Р В Р вЂ¦Р В РЎвЂў.');
        } else {
            $correctAnswer = (string) $result['correct_answer'];
            $this->bot->sendMessage($chatId, "Р В РЎСљР В Р’ВµР В РЎвЂќР В РЎвЂўР РЋР вЂљР РЋР вЂљР В Р’ВµР В РЎвЂќР РЋРІР‚С™Р В Р вЂ¦Р В РЎвЂў. Р В РЎСџР РЋР вЂљР В Р’В°Р В Р вЂ Р В РЎвЂР В Р’В»Р РЋР Р‰Р В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™: {$correctAnswer}");
        }

        /** @var TelegramGameRun $freshRun */
        $freshRun = $result['run'];

        if ($result['next_item'] !== null) {
            $this->telegramGameRuntimeService->sendQuestion($freshRun, $result['next_item']);

            return;
        }

        $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
            ? $result['summary_text']
            : 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°.';

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
            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦Р В Р’В°.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_CANCEL) {
            $result = $this->telegramIntervalReviewRuntimeService->cancelRun($run);

            if ($result['status'] === 'cancelled') {
                Log::info('telegram.interval_review.run_cancelled', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'Р В Р’В­Р РЋРІР‚С™Р В Р’В° Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В РЎвЂР В Р вЂ¦Р РЋРІР‚С™Р В Р’ВµР РЋР вЂљР В Р вЂ Р В Р’В°Р В Р’В»Р РЋР Р‰Р В Р вЂ¦Р В РЎвЂўР В РЎвЂ“Р В РЎвЂў Р В РЎвЂ”Р В РЎвЂўР В Р вЂ Р РЋРІР‚С™Р В РЎвЂўР РЋР вЂљР В Р’ВµР В Р вЂ¦Р В РЎвЂР РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°. Р В Р Р‹Р В Р’В»Р В Р’ВµР В РўвЂР РЋРЎвЂњР РЋР вЂ№Р РЋРІР‚В°Р В РЎвЂР В Р’Вµ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР В РЎвЂ Р В РЎвЂ”Р В Р’В»Р В Р’В°Р В Р вЂ¦Р В Р’В° Р В РЎвЂ”Р РЋР вЂљР В РЎвЂўР В РўвЂР В РЎвЂўР В Р’В»Р В Р’В¶Р В Р’В°Р РЋРІР‚С™ Р РЋР вЂљР В Р’В°Р В Р’В±Р В РЎвЂўР РЋРІР‚С™Р В Р’В°Р РЋРІР‚С™Р РЋР Р‰ Р В РЎвЂ”Р В РЎвЂў Р РЋР вЂљР В Р’В°Р РЋР С“Р В РЎвЂ”Р В РЎвЂР РЋР С“Р В Р’В°Р В Р вЂ¦Р В РЎвЂР РЋР вЂ№.');

                return;
            }

            if ($result['status'] === 'already_cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р’В­Р РЋРІР‚С™Р РЋРЎвЂњ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР вЂ№ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В Р’В»Р РЋР Р‰Р В Р’В·Р РЋР РЏ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В РЎвЂР РЋРІР‚С™Р РЋР Р‰.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_START) {
            $result = $this->telegramIntervalReviewRuntimeService->startRun($run);

            if ($result['status'] === 'started') {
                Log::info('telegram.interval_review.run_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋРІР‚В°Р В Р’ВµР В Р вЂ¦Р В Р’В°.');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->telegramIntervalReviewRuntimeService->sendWordList($result['run']);

                return;
            }

            if ($result['status'] === 'finished_without_items') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚в„ў Р РЋР РЉР РЋРІР‚С™Р В РЎвЂўР В РІвЂћвЂ“ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР В РЎвЂ Р В Р вЂ¦Р В Р’ВµР РЋРІР‚С™ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р РЋРІР‚В¦ Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ .');
                $this->clearInlineKeyboard($chatId, $messageId);
                $this->bot->sendMessage($chatId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°: Р В РўвЂР В Р’В»Р РЋР РЏ Р В Р вЂ¦Р В Р’ВµР РЋРІР‚В Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р РЋРІвЂљВ¬Р В Р’В»Р В РЎвЂўР РЋР С“Р РЋР Р‰ Р В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р РЋРІР‚в„–Р РЋРІР‚В¦ Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ .');

                return;
            }

            if ($result['status'] === 'already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋРІР‚В°Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

                return;
            }

            if ($result['status'] === 'cancelled') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В РЎвЂўР РЋРІР‚С™Р В РЎВР В Р’ВµР В Р вЂ¦Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р’В­Р РЋРІР‚С™Р РЋРЎвЂњ Р РЋР С“Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР вЂ№ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В Р’В»Р РЋР Р‰Р В Р’В·Р РЋР РЏ Р В Р’В·Р В Р’В°Р В РЎвЂ”Р РЋРЎвЂњР РЋР С“Р РЋРІР‚С™Р В РЎвЂР РЋРІР‚С™Р РЋР Р‰.');

            return;
        }

        if (($payload['type'] ?? null) === 'run_action' && ($payload['action'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_BEGIN_QUIZ) {
            $result = $this->telegramIntervalReviewRuntimeService->beginQuiz($run);

            if ($result['status'] === 'quiz_started') {
                Log::info('telegram.interval_review.quiz_started', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $run->user_id,
                ]);

                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎв„ўР В Р вЂ Р В РЎвЂР В Р’В· Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р РЋРІР‚С™.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            if ($result['status'] === 'finished_without_questions') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°.');

                if ($messageId !== null) {
                    $this->bot->deleteMessage($chatId, $messageId);
                }

                $this->bot->sendMessage($chatId, (string) ($result['summary_text'] ?? 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°.'));

                return;
            }

            if ($result['status'] === 'quiz_already_started') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎв„ўР В Р вЂ Р В РЎвЂР В Р’В· Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р РЋРІР‚С™.');

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР вЂ№ Р РЋР С“Р В Р’ВµР В РІвЂћвЂ“Р РЋРІР‚РЋР В Р’В°Р РЋР С“ Р В Р вЂ¦Р В Р’ВµР В Р’В»Р РЋР Р‰Р В Р’В·Р РЋР РЏ Р В РЎвЂ”Р В Р’ВµР РЋР вЂљР В Р’ВµР В Р вЂ Р В Р’ВµР РЋР С“Р РЋРІР‚С™Р В РЎвЂ Р В Р вЂ  Р РЋР вЂљР В Р’ВµР В Р’В¶Р В РЎвЂР В РЎВ Р В РЎвЂќР В Р вЂ Р В РЎвЂР В Р’В·Р В Р’В°.');

            return;
        }

        if (($payload['type'] ?? null) === TelegramIntervalReviewRunCallbackData::ACTION_ANSWER) {
            $result = $this->telegramIntervalReviewRuntimeService->submitAnswer(
                $run,
                (int) $payload['item_id'],
                (int) $payload['option_index'],
            );

            if ($result['status'] === 'run_not_in_progress') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р РЋР С“Р В Р’ВµР В РІвЂћвЂ“Р РЋРІР‚РЋР В Р’В°Р РЋР С“ Р В Р вЂ¦Р В Р’Вµ Р В Р’В°Р В РЎвЂќР РЋРІР‚С™Р В РЎвЂР В Р вЂ Р В Р вЂ¦Р В Р’В°.');

                return;
            }

            if ($result['status'] === 'item_not_found') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚в„ўР В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“ Р В Р вЂ¦Р В Р’Вµ Р В Р вЂ¦Р В Р’В°Р В РІвЂћвЂ“Р В РўвЂР В Р’ВµР В Р вЂ¦.');

                return;
            }

            if ($result['status'] === 'already_answered') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎСљР В Р’В° Р РЋР РЉР РЋРІР‚С™Р В РЎвЂўР РЋРІР‚С™ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“ Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р В РЎвЂР В Р’В»Р В РЎвЂ.');

                return;
            }

            if ($result['status'] === 'wrong_item') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В Р Р‹Р В Р вЂ¦Р В Р’В°Р РЋРІР‚РЋР В Р’В°Р В Р’В»Р В Р’В° Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р РЋР Р‰Р РЋРІР‚С™Р В Р’Вµ Р В Р вЂ¦Р В Р’В° Р РЋРІР‚С™Р В Р’ВµР В РЎвЂќР РЋРЎвЂњР РЋРІР‚В°Р В РЎвЂР В РІвЂћвЂ“ Р В Р вЂ Р В РЎвЂўР В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“.');

                return;
            }

            if ($result['status'] === 'invalid_option') {
                $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎС›Р В Р’В°Р В РЎвЂќР В РЎвЂўР В РІвЂћвЂ“ Р В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂР В Р’В°Р В Р вЂ¦Р РЋРІР‚С™ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™Р В Р’В° Р В Р вЂ¦Р В Р’ВµР В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р’ВµР В Р вЂ¦.');

                return;
            }

            Log::info('telegram.interval_review.answer_accepted', [
                'telegram_interval_review_run_id' => $run->id,
                'user_id' => $run->user_id,
                'item_id' => (int) $payload['item_id'],
                'option_index' => (int) $payload['option_index'],
                'is_correct' => $result['is_correct'],
            ]);

            $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РЎвЂєР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™ Р В РЎвЂ”Р РЋР вЂљР В РЎвЂР В Р вЂ¦Р РЋР РЏР РЋРІР‚С™.');
            $this->clearInlineKeyboard($chatId, $messageId);

            if (($result['is_correct'] ?? false) === true) {
                $this->bot->sendMessage($chatId, 'Р В РЎв„ўР В РЎвЂўР РЋР вЂљР РЋР вЂљР В Р’ВµР В РЎвЂќР РЋРІР‚С™Р В Р вЂ¦Р В РЎвЂў.');
            } else {
                $this->bot->sendMessage($chatId, 'Р В РЎСљР В Р’ВµР В РЎвЂќР В РЎвЂўР РЋР вЂљР РЋР вЂљР В Р’ВµР В РЎвЂќР РЋРІР‚С™Р В Р вЂ¦Р В РЎвЂў. Р В РЎСџР РЋР вЂљР В Р’В°Р В Р вЂ Р В РЎвЂР В Р’В»Р РЋР Р‰Р В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“ Р В РЎвЂўР РЋРІР‚С™Р В Р вЂ Р В Р’ВµР РЋРІР‚С™: '.(string) $result['correct_answer']);
            }

            if (($result['next_item'] ?? null) !== null) {
                $this->telegramIntervalReviewRuntimeService->sendQuestion($result['run'], $result['next_item']);

                return;
            }

            $summaryText = is_string($result['summary_text'] ?? null) && $result['summary_text'] !== ''
                ? $result['summary_text']
                : 'Р В Р Р‹Р В Р’ВµР РЋР С“Р РЋР С“Р В РЎвЂР РЋР РЏ Р В Р’В·Р В Р’В°Р В Р вЂ Р В Р’ВµР РЋР вЂљР РЋРІвЂљВ¬Р В Р’ВµР В Р вЂ¦Р В Р’В°.';

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

        $this->bot->answerCallbackQuery($callbackQueryId, 'Р В РІР‚СњР В Р’ВµР В РІвЂћвЂ“Р РЋР С“Р РЋРІР‚С™Р В Р вЂ Р В РЎвЂР В Р’Вµ Р В Р вЂ¦Р В Р’ВµР В РўвЂР В РЎвЂўР РЋР С“Р РЋРІР‚С™Р РЋРЎвЂњР В РЎвЂ”Р В Р вЂ¦Р В РЎвЂў.');
    }

    private function handleEmailStep(string $chatId, string $text, ?string $username): void
    {
        $email = mb_strtolower(trim($text));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->bot->sendMessage($chatId, 'Р СњРЎС“Р В¶Р ВµР Р… Р С”Р С•РЎР‚РЎР‚Р ВµР С”РЎвЂљР Р…РЎвЂ№Р в„– email. Р СџР С•Р С—РЎР‚Р С•Р В±РЎС“Р в„–РЎвЂљР Вµ Р ВµРЎвЂ°РЎвЂ РЎР‚Р В°Р В·.');

            return;
        }

        $this->stateStore->clear($chatId);

        $result = $this->telegramLoginIntentService->startForEmail($chatId, $username, $email);

        if ($result['status'] === 'user_not_found') {
            $this->bot->sendMessage($chatId, 'Р С’Р С”Р С”Р В°РЎС“Р Р…РЎвЂљ РЎРѓ РЎвЂљР В°Р С”Р С‘Р С email Р Р…Р Вµ Р Р…Р В°Р в„–Р Т‘Р ВµР Р…. Р вЂ”Р В°РЎР‚Р ВµР С–Р С‘РЎРѓРЎвЂљРЎР‚Р С‘РЎР‚РЎС“Р в„–РЎвЂљР ВµРЎРѓРЎРЉ Р Р…Р В° РЎРѓР В°Р в„–РЎвЂљР Вµ: '.route('register'));

            return;
        }

        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", [
                'Р С’Р С”Р С”Р В°РЎС“Р Р…РЎвЂљ Р Р…Р В°Р в„–Р Т‘Р ВµР Р…. Р вЂќР В»РЎРЏ Р С—Р С•Р Т‘РЎвЂљР Р†Р ВµРЎР‚Р В¶Р Т‘Р ВµР Р…Р С‘РЎРЏ Р В°Р Р†РЎвЂљР С•РЎР‚Р С‘Р В·РЎС“Р в„–РЎвЂљР ВµРЎРѓРЎРЉ Р Р…Р В° РЎРѓР В°Р в„–РЎвЂљР Вµ.',
                $result['url'],
            ]),
        );
    }

    private function handleLogout(string $chatId): void
    {
        $this->telegramAccountLinkService->unlinkByChatId($chatId);

        $this->bot->sendMessage(
            $chatId,
            'Telegram-Р В°Р С”Р С”Р В°РЎС“Р Р…РЎвЂљ Р С•РЎвЂљР С”Р В»РЎР‹РЎвЂЎРЎвЂР Р…. Р вЂќР В»РЎРЏ Р С—Р С•Р Р†РЎвЂљР С•РЎР‚Р Р…Р С•Р в„– Р С—РЎР‚Р С‘Р Р†РЎРЏР В·Р С”Р С‘ Р С•РЎвЂљР С—РЎР‚Р В°Р Р†РЎРЉРЎвЂљР Вµ /login.',
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
                    'Р В Р’В­Р РЋРІР‚С™Р В РЎвЂў Telegram-Р В Р’В±Р В РЎвЂўР РЋРІР‚С™ WordKeeper.',
                    'Р В РІР‚в„ўР РЋРІР‚в„– Р РЋРЎвЂњР В Р’В¶Р В Р’Вµ Р В Р’В°Р В Р вЂ Р РЋРІР‚С™Р В РЎвЂўР РЋР вЂљР В РЎвЂР В Р’В·Р В РЎвЂўР В Р вЂ Р В Р’В°Р В Р вЂ¦Р РЋРІР‚в„– Р В РЎвЂ Р В РЎВР В РЎвЂўР В Р’В¶Р В Р’ВµР РЋРІР‚С™Р В Р’Вµ Р В РЎвЂ”Р РЋР вЂљР В РЎвЂўР РЋР С“Р В РЎВР В Р’В°Р РЋРІР‚С™Р РЋР вЂљР В РЎвЂР В Р вЂ Р В Р’В°Р РЋРІР‚С™Р РЋР Р‰ Р РЋР С“Р В Р вЂ Р В РЎвЂўР В РЎвЂ Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂ Р В РЎвЂ”Р РЋР вЂљР РЋР РЏР В РЎВР В РЎвЂў Р В РЎвЂР В Р’В· Telegram.',
                    'Р В РЎСљР В Р’В°Р В Р’В¶Р В РЎВР В РЎвЂР РЋРІР‚С™Р В Р’Вµ Р вЂ™Р’В«Р В Р Р‹Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂР вЂ™Р’В», Р РЋРІР‚РЋР РЋРІР‚С™Р В РЎвЂўР В Р’В±Р РЋРІР‚в„– Р В РЎвЂўР РЋРІР‚С™Р В РЎвЂќР РЋР вЂљР РЋРІР‚в„–Р РЋРІР‚С™Р РЋР Р‰ Р РЋР С“Р В РЎвЂ”Р В РЎвЂР РЋР С“Р В РЎвЂўР В РЎвЂќ Р В Р вЂ Р В Р’В°Р РЋРІвЂљВ¬Р В РЎвЂР РЋРІР‚В¦ Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР В Р’ВµР В РІвЂћвЂ“.',
                ]),
                $this->mainMenuReplyMarkup(),
            );

            return;
        }

        $this->bot->sendMessage(
            $chatId,
            implode("\n\n", [
                'Р В Р’В­Р РЋРІР‚С™Р В РЎвЂў Telegram-Р В Р’В±Р В РЎвЂўР РЋРІР‚С™ WordKeeper.',
                'Р В РЎвЂєР В Р вЂ¦ Р В Р вЂ¦Р РЋРЎвЂњР В Р’В¶Р В Р’ВµР В Р вЂ¦ Р В РўвЂР В Р’В»Р РЋР РЏ Р РЋР вЂљР В Р’В°Р В Р’В±Р В РЎвЂўР РЋРІР‚С™Р РЋРІР‚в„– Р РЋР С“Р В РЎвЂў Р РЋР С“Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР РЋР РЏР В РЎВР В РЎвЂ Р В РЎвЂ Р РЋРІР‚С™Р РЋР вЂљР В Р’ВµР В Р вЂ¦Р В РЎвЂР РЋР вЂљР В РЎвЂўР В Р вЂ Р В РЎвЂќР В Р’В°Р В РЎВР В РЎвЂ Р В РЎвЂ”Р РЋР вЂљР РЋР РЏР В РЎВР В РЎвЂў Р В РЎвЂР В Р’В· Telegram.',
                'Р В Р’В§Р РЋРІР‚С™Р В РЎвЂўР В Р’В±Р РЋРІР‚в„– Р В РЎвЂ”Р В РЎвЂўР В РўвЂР В РЎвЂќР В Р’В»Р РЋР вЂ№Р РЋРІР‚РЋР В РЎвЂР РЋРІР‚С™Р РЋР Р‰ Р В Р’В±Р В РЎвЂўР РЋРІР‚С™Р В Р’В° Р В РЎвЂќ Р В Р вЂ Р В Р’В°Р РЋРІвЂљВ¬Р В Р’ВµР В РЎВР РЋРЎвЂњ Р В Р’В°Р В РЎвЂќР В РЎвЂќР В Р’В°Р РЋРЎвЂњР В Р вЂ¦Р РЋРІР‚С™Р РЋРЎвЂњ Р РЋР С“Р В Р’В°Р В РІвЂћвЂ“Р РЋРІР‚С™Р В Р’В°, Р В РЎвЂўР РЋРІР‚С™Р В РЎвЂ”Р РЋР вЂљР В Р’В°Р В Р вЂ Р РЋР Р‰Р РЋРІР‚С™Р В Р’Вµ /login.',
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
                    [['text' => 'Р В Р Р‹Р В Р’В»Р В РЎвЂўР В Р вЂ Р В Р’В°Р РЋР вЂљР В РЎвЂ']],
                    [['text' => 'Р В РІР‚в„ўР РЋРІР‚в„–Р РЋРІР‚В¦Р В РЎвЂўР В РўвЂ']],
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
