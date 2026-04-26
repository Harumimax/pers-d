<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Throwable;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Set Telegram webhook for the configured bot.';

    public function handle(TelegramBotService $botService): int
    {
        $appUrl = (string) config('app.url');
        $botToken = (string) config('services.telegram.bot_token');
        $secret = (string) config('services.telegram.webhook_secret');

        if ($appUrl === '') {
            $this->error('APP_URL is not configured.');

            return self::FAILURE;
        }

        if ($botToken === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not configured.');

            return self::FAILURE;
        }

        try {
            $url = route('telegram.webhook', ['secret' => $secret], true);
            $result = $botService->setWebhook($url);

            $this->info('Telegram webhook has been set.');
            $this->line('Webhook URL: '.$url);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
