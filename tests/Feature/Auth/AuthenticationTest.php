<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Remember me')
            ->assertSee('Forgot your password?')
            ->assertSee('Log in');
    }

    public function test_login_screen_is_translated_to_russian_when_locale_is_set(): void
    {
        $this->withSession(['ui_locale' => 'ru'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Пароль')
            ->assertSee('Запомнить меня')
            ->assertSee('Забыли пароль?')
            ->assertSee('Войти');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dictionaries.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_invalid_login_message_is_localized_to_russian(): void
    {
        $user = User::factory()->create();

        $this->withSession(['ui_locale' => 'ru'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => __('auth.failed'),
            ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
