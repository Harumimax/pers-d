<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
            ->assertSee('Забыли пароль?')
            ->assertSee('Отправить ссылку для сброса пароля');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
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

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
            return $notification->locale === 'ru';
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
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

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $this->withSession(['ui_locale' => 'ru'])
                ->get('/reset-password/'.$notification->token)
                ->assertOk()
                ->assertSee('Пароль')
                ->assertSee('Подтвердите пароль')
                ->assertSee('Сбросить пароль');

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
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
