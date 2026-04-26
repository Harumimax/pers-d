<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotService
{
    public function sendMessage(string $chatId, string $text, array $extra = []): array
    {
        return $this->post('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    public function setWebhook(string $url): array
    {
        return $this->post('setWebhook', [
            'url' => $url,
        ]);
    }

    private function post(string $method, array $payload): array
    {
        $response = Http::asJson()
            ->acceptJson()
            ->timeout(20)
            ->post($this->apiBaseUrl().'/'.$method, $payload);

        $response->throw();

        return $response->json() ?? [];
    }

    private function apiBaseUrl(): string
    {
        $botToken = (string) config('services.telegram.bot_token');

        if ($botToken === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        return 'https://api.telegram.org/bot'.$botToken;
    }
}
