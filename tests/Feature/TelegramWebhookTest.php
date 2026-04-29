<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAuthStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_successful_authorization_saves_telegram_chat_id_and_username(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login', 'wordkeeper_user', 10001));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('tester@example.com', 'wordkeeper_user', 10002));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('secret-password', 'new_tg_login', 10003));

        $user->refresh();

        $this->assertSame('1001', $user->tg_chat_id);
        $this->assertSame('new_tg_login', $user->tg_login);
        $this->assertNotNull($user->tg_linked_at);
        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], 'Telegram'));
    }

    public function test_repeat_authorization_updates_telegram_username(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
            'tg_login' => 'old_login',
            'tg_chat_id' => '9999',
            'tg_linked_at' => now()->subDay(),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login', 'fresh_login', 10001));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('tester@example.com', 'fresh_login', 10002));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('secret-password', 'fresh_login', 10003));

        $user->refresh();

        $this->assertSame('1001', $user->tg_chat_id);
        $this->assertSame('fresh_login', $user->tg_login);
    }

    public function test_invalid_password_does_not_link_telegram_to_user(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/login', 'wordkeeper_user', 10001));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('tester@example.com', 'wordkeeper_user', 10002));
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('wrong-password', 'wordkeeper_user', 10003));

        $user->refresh();

        $this->assertNull($user->tg_chat_id);
        $this->assertNull($user->tg_linked_at);
        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], '/login'));
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
