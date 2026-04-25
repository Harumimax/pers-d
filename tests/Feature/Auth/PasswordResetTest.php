<?php

namespace Tests\Feature\Auth;

use App\Notifications\Auth\ResetPasswordViaNotiSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200)
            ->assertSee('Forgot your password?')
            ->assertSee('Email Password Reset Link');
    }

    public function test_reset_password_link_screen_is_translated_to_russian_when_locale_is_set(): void
    {
        $this->withSession(['ui_locale' => 'ru'])
            ->get('/forgot-password')
            ->assertOk()
            ->assertSee(__('auth.forgot_password.description'))
            ->assertSee(__('auth.forgot_password.submit'));
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordViaNotiSend::class);
    }

    public function test_reset_password_link_request_returns_localized_status_message(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->withSession(['ui_locale' => 'ru'])
            ->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_reset_password_notification_uses_users_preferred_locale(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'preferred_locale' => 'ru',
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordViaNotiSend::class, function (ResetPasswordViaNotiSend $notification) {
            return $notification->locale === 'ru';
        });
    }

    public function test_reset_password_notification_is_queued(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job): bool {
            return $job->notification instanceof ResetPasswordViaNotiSend
                && $job->channels === ['App\\Notifications\\Channels\\NotiSendMailChannel'];
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordViaNotiSend::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_reset_password_screen_is_translated_to_russian_when_locale_is_set(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordViaNotiSend::class, function ($notification) {
            $this->withSession(['ui_locale' => 'ru'])
                ->get('/reset-password/'.$notification->token)
                ->assertOk()
                ->assertSee(__('auth.reset_password.password'))
                ->assertSee(__('auth.reset_password.password_confirmation'))
                ->assertSee(__('auth.reset_password.submit'));

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordViaNotiSend::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_password_notification_uses_notisend_api_channel_to_send_email(): void
    {
        config([
            'services.notisend.api_token' => 'test-token',
            'services.notisend.base_url' => 'https://api.notisend.ru/v1',
            'services.notisend.reserve_base_url' => 'https://api-reserve.msndr.net/v1',
            'services.notisend.from_email' => 'sender@example.com',
            'services.notisend.from_name' => 'WordKeeper',
        ]);

        Http::fake([
            'https://api.notisend.ru/v1/email/messages' => Http::response([
                'id' => 42,
                'status' => 'queued',
            ], 201),
        ]);

        $user = User::factory()->create([
            'preferred_locale' => 'en',
        ]);

        $notification = new ResetPasswordViaNotiSend('test-reset-token');
        $notification->locale('en');

        app('Illuminate\\Notifications\\ChannelManager')->sendNow($user, $notification);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($user): bool {
            return $request->url() === 'https://api.notisend.ru/v1/email/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['from_email'] === 'sender@example.com'
                && $request['from_name'] === 'WordKeeper'
                && $request['to'] === $user->email
                && $request['subject'] === 'Reset your password'
                && str_contains((string) $request['text'], '/reset-password/test-reset-token?email=')
                && str_contains((string) $request['html'], '/reset-password/test-reset-token?email=');
        });
    }

    public function test_invalid_email_for_password_reset_returns_localized_error(): void
    {
        $this->withSession(['ui_locale' => 'ru'])
            ->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors([
                'email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            ]);
    }
}
