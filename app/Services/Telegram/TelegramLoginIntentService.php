<?php

namespace App\Services\Telegram;

use App\Models\TelegramLoginIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramLoginIntentService
{
    private const TTL_MINUTES = 15;

    public function __construct(
        private readonly TelegramAccountLinkService $telegramAccountLinkService,
        private readonly TelegramBotService $telegramBotService,
    ) {
    }

    /**
     * @return array{status:'user_not_found'}|array{status:'intent_created',url:string,intent:TelegramLoginIntent}
     */
    public function startForEmail(string $chatId, ?string $telegramUsername, string $email): array
    {
        $normalizedEmail = Str::lower(trim($email));

        /** @var User|null $user */
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (! $user instanceof User) {
            return ['status' => 'user_not_found'];
        }

        $rawToken = Str::random(80);
        $tokenHash = hash('sha256', $rawToken);

        DB::transaction(function () use ($chatId, $user, $normalizedEmail, $telegramUsername, $tokenHash): void {
            TelegramLoginIntent::query()
                ->where('status', TelegramLoginIntent::STATUS_PENDING)
                ->where('chat_id', $chatId)
                ->update([
                    'status' => TelegramLoginIntent::STATUS_CANCELLED,
                ]);

            TelegramLoginIntent::query()->create([
                'user_id' => $user->getKey(),
                'chat_id' => $chatId,
                'telegram_username' => $telegramUsername,
                'email' => $normalizedEmail,
                'token_hash' => $tokenHash,
                'status' => TelegramLoginIntent::STATUS_PENDING,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });

        /** @var TelegramLoginIntent $intent */
        $intent = TelegramLoginIntent::query()
            ->where('token_hash', $tokenHash)
            ->firstOrFail();

        return [
            'status' => 'intent_created',
            'url' => route('telegram-auth.show', ['token' => $rawToken]),
            'intent' => $intent,
        ];
    }

    public function findValidByToken(string $token): ?TelegramLoginIntent
    {
        $intent = $this->findPendingIntentByToken($token);

        return $intent?->load('user');
    }

    /**
     * @return array{status:'invalid_or_expired'}|array{status:'invalid_credentials',intent:TelegramLoginIntent}|array{status:'linked',intent:TelegramLoginIntent,telegram_notification_sent:bool}
     */
    public function consumeWithPassword(string $token, string $password): array
    {
        $result = DB::transaction(function () use ($token, $password): array {
            $intent = $this->findPendingIntentByToken($token, true);

            if (! $intent instanceof TelegramLoginIntent) {
                return ['status' => 'invalid_or_expired'];
            }

            $intent->load('user');

            if (! $intent->user instanceof User || ! Hash::check($password, (string) $intent->user->password)) {
                return [
                    'status' => 'invalid_credentials',
                    'intent' => $intent,
                ];
            }

            $this->telegramAccountLinkService->link($intent->user, $intent->chat_id, $intent->telegram_username);

            $intent->forceFill([
                'status' => TelegramLoginIntent::STATUS_CONSUMED,
                'consumed_at' => now(),
            ])->save();

            return [
                'status' => 'linked',
                'intent' => $intent->fresh(['user']),
            ];
        });

        if (($result['status'] ?? null) !== 'linked') {
            return $result;
        }

        $result['telegram_notification_sent'] = $this->sendSuccessTelegramMessage($result['intent']->chat_id);

        return $result;
    }

    private function sendSuccessTelegramMessage(string $chatId): bool
    {
        try {
            $this->telegramBotService->sendMessage(
                $chatId,
                'Вы успешно авторизованы в боте WordKeeper.',
                [
                    'reply_markup' => [
                        'keyboard' => [
                            [['text' => 'Словари']],
                            [['text' => 'Поиск слов']],
                            [['text' => 'Выход']],
                        ],
                        'resize_keyboard' => true,
                    ],
                ],
            );

            return true;
        } catch (\Throwable $exception) {
            Log::error('telegram.auth.intent.success_notification_failed', [
                'chat_id' => $chatId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function findPendingIntentByToken(string $token, bool $forUpdate = false): ?TelegramLoginIntent
    {
        $query = TelegramLoginIntent::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('status', TelegramLoginIntent::STATUS_PENDING);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        /** @var TelegramLoginIntent|null $intent */
        $intent = $query->first();

        if (! $intent instanceof TelegramLoginIntent) {
            return null;
        }

        if ($intent->expires_at->isPast()) {
            $intent->forceFill([
                'status' => TelegramLoginIntent::STATUS_EXPIRED,
            ])->save();

            return null;
        }

        return $intent;
    }
}
