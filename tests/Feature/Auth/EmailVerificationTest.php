<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200)
            ->assertSee('Thanks for signing up!')
            ->assertSee('Resend Verification Email')
            ->assertSee('Log Out');
    }

    public function test_email_verification_screen_is_translated_to_russian_when_locale_is_set(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Спасибо за регистрацию!')
            ->assertSee('Отправить письмо повторно')
            ->assertSee('Выйти');
    }

    public function test_resend_verification_uses_users_preferred_locale(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'preferred_locale' => 'ru',
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) {
            return $notification->locale === 'ru';
        });
    }

    public function test_resend_verification_returns_localized_status_message(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->from('/verify-email')
            ->post(route('verification.send'))
            ->assertRedirect('/verify-email')
            ->assertSessionHas('status', 'verification-link-sent');

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get('/verify-email')
            ->assertSee('Новая ссылка для подтверждения была отправлена');
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
