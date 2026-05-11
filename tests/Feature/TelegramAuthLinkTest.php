<?php

namespace Tests\Feature;

use App\Models\TelegramLoginIntent;
use App\Models\User;
use App\Services\Telegram\TelegramLoginIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAuthLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'telegram-test-token',
        ]);
    }

    public function test_confirmation_page_renders_for_valid_token(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
        ]);

        $result = app(TelegramLoginIntentService::class)->startForEmail('1001', 'wordkeeper_user', $user->email);

        $this->get($result['url'])
            ->assertOk()
            ->assertSee('tester@example.com')
            ->assertSee(__('auth.telegram_link.title'));
    }

    public function test_successful_password_confirmation_links_telegram_account_without_browser_login(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $result = app(TelegramLoginIntentService::class)->startForEmail('1001', 'new_tg_login', $user->email);

        $this->post($result['url'], [
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertSee(__('auth.telegram_link.success_title'));

        $user->refresh();
        $intent = TelegramLoginIntent::query()->first();

        $this->assertSame('1001', $user->tg_chat_id);
        $this->assertSame('new_tg_login', $user->tg_login);
        $this->assertNotNull($user->tg_linked_at);
        $this->assertSame(TelegramLoginIntent::STATUS_CONSUMED, $intent->status);
        $this->assertNotNull($intent->consumed_at);
        $this->assertGuest();

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains((string) $request['text'], 'WordKeeper'));
    }

    public function test_invalid_password_does_not_consume_intent_or_link_user(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $result = app(TelegramLoginIntentService::class)->startForEmail('1001', 'wordkeeper_user', $user->email);

        $this->from($result['url'])
            ->post($result['url'], [
                'password' => 'wrong-password',
            ])
            ->assertRedirect($result['url'])
            ->assertSessionHasErrors('password');

        $user->refresh();
        $intent = TelegramLoginIntent::query()->first();

        $this->assertNull($user->tg_chat_id);
        $this->assertNull($user->tg_linked_at);
        $this->assertSame(TelegramLoginIntent::STATUS_PENDING, $intent->status);

        Http::assertNothingSent();
    }

    public function test_expired_or_consumed_token_returns_gone(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $result = app(TelegramLoginIntentService::class)->startForEmail('1001', 'wordkeeper_user', $user->email);

        $intent = TelegramLoginIntent::query()->firstOrFail();
        $intent->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $this->get($result['url'])
            ->assertStatus(410)
            ->assertSee(__('auth.telegram_link.invalid_title'));
    }
}
