<?php

namespace Tests\Feature;

use App\Models\TelegramLoginIntent;
use App\Models\User;
use App\Services\Telegram\TelegramAuthStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'telegram-test-token',
            'services.telegram.webhook_secret' => 'telegram-secret',
        ]);
    }

    public function test_webhook_with_invalid_secret_returns_403(): void
    {
        Http::fake();

        $this->postJson('/telegram/webhook/wrong-secret', $this->messageUpdate('/start'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_start_sends_greeting_message(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/start'))
            ->assertOk();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bottelegram-test-token/sendMessage'
                && $request['chat_id'] === '1001'
                && str_contains((string) $request['text'], 'WordKeeper')
                && str_contains((string) $request['text'], '/login');
        });
    }

    public function test_login_starts_login_flow(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login'))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');

        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_EMAIL, $state['step']);
        $this->assertNull($state['email']);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], 'email'));
    }

    public function test_email_step_creates_one_time_link_for_existing_account(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'email' => 'tester@example.com',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login', 'wordkeeper_user', 10001));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('tester@example.com', 'wordkeeper_user', 10002))
            ->assertOk();

        $intent = TelegramLoginIntent::query()->first();

        $this->assertNotNull($intent);
        $this->assertSame('1001', $intent->chat_id);
        $this->assertSame('tester@example.com', $intent->email);
        $this->assertSame(TelegramLoginIntent::STATUS_PENDING, $intent->status);
        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], '/telegram-auth/'));
    }

    public function test_email_step_reports_missing_account_and_suggests_registration(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login', 'wordkeeper_user', 10001));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('missing@example.com', 'wordkeeper_user', 10002))
            ->assertOk();

        $this->assertDatabaseCount('telegram_login_intents', 0);
        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], '/register'));
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(string $text, string $username = 'wordkeeper_user', int $updateId = 10000): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 5001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => $username,
                ],
                'chat' => [
                    'id' => 1001,
                    'type' => 'private',
                ],
                'date' => now()->timestamp,
                'text' => $text,
            ],
        ];
    }
}
