<?php

namespace App\Services\Telegram;

use App\Models\TelegramProcessedUpdate;
use Illuminate\Support\Facades\DB;

class TelegramProcessedUpdateService
{
    public function claim(array $update): ?TelegramProcessedUpdate
    {
        $descriptor = $this->describe($update);

        if ($descriptor['telegram_update_id'] === null && $descriptor['callback_query_id'] === null) {
            return null;
        }

        return DB::transaction(function () use ($descriptor): ?TelegramProcessedUpdate {
            $existing = TelegramProcessedUpdate::query()
                ->when(
                    $descriptor['telegram_update_id'] !== null,
                    fn ($query) => $query->orWhere('telegram_update_id', $descriptor['telegram_update_id'])
                )
                ->when(
                    $descriptor['callback_query_id'] !== null,
                    fn ($query) => $query->orWhere('callback_query_id', $descriptor['callback_query_id'])
                )
                ->lockForUpdate()
                ->first();

            if ($existing instanceof TelegramProcessedUpdate) {
                if (in_array($existing->status, [
                    TelegramProcessedUpdate::STATUS_PROCESSING,
                    TelegramProcessedUpdate::STATUS_PROCESSED,
                ], true)) {
                    return null;
                }

                $existing->forceFill([
                    'chat_id' => $descriptor['chat_id'],
                    'update_type' => $descriptor['update_type'],
                    'status' => TelegramProcessedUpdate::STATUS_PROCESSING,
                    'attempts' => $existing->attempts + 1,
                    'last_error_message' => null,
                    'processed_at' => null,
                ])->save();

                return $existing->fresh();
            }

            return TelegramProcessedUpdate::query()->create([
                'telegram_update_id' => $descriptor['telegram_update_id'],
                'callback_query_id' => $descriptor['callback_query_id'],
                'chat_id' => $descriptor['chat_id'],
                'update_type' => $descriptor['update_type'],
                'status' => TelegramProcessedUpdate::STATUS_PROCESSING,
                'attempts' => 1,
            ]);
        });
    }

    public function markProcessed(TelegramProcessedUpdate $processedUpdate): void
    {
        $processedUpdate->forceFill([
            'status' => TelegramProcessedUpdate::STATUS_PROCESSED,
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(TelegramProcessedUpdate $processedUpdate, string $message): void
    {
        $processedUpdate->forceFill([
            'status' => TelegramProcessedUpdate::STATUS_FAILED,
            'last_error_message' => mb_substr($message, 0, 2000),
        ])->save();
    }

    /**
     * @return array{
     *   telegram_update_id:?int,
     *   callback_query_id:?string,
     *   chat_id:?string,
     *   update_type:string
     * }
     */
    private function describe(array $update): array
    {
        $updateId = $update['update_id'] ?? null;
        $callbackQueryId = trim((string) data_get($update, 'callback_query.id', ''));
        $chatId = data_get($update, 'callback_query.message.chat.id')
            ?? data_get($update, 'message.chat.id');

        return [
            'telegram_update_id' => is_numeric($updateId) ? (int) $updateId : null,
            'callback_query_id' => $callbackQueryId !== '' ? $callbackQueryId : null,
            'chat_id' => $chatId !== null ? (string) $chatId : null,
            'update_type' => is_array($update['callback_query'] ?? null) ? 'callback_query' : 'message',
        ];
    }
}
