<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TelegramAccountLinkService
{
    public function link(User $user, string $chatId, ?string $username): void
    {
        DB::transaction(function () use ($user, $chatId, $username): void {
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
    }

    public function unlinkByChatId(string $chatId): void
    {
        User::query()
            ->where('tg_chat_id', $chatId)
            ->update([
                'tg_chat_id' => null,
                'tg_linked_at' => null,
            ]);
    }
}
