<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertStatus(200)
            ->assertSee('This is a secure area of the application.')
            ->assertSee('Password')
            ->assertSee('Confirm');
    }

    public function test_confirm_password_screen_is_translated_to_russian_when_locale_is_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get('/confirm-password')
            ->assertOk()
            ->assertSee('Это защищенный раздел приложения')
            ->assertSee('Пароль')
            ->assertSee('Подтвердить');
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_invalid_password_confirmation_error_is_localized(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->from('/confirm-password')
            ->post('/confirm-password', [
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/confirm-password')
            ->assertSessionHasErrors([
                'password' => __('auth.password'),
            ]);
    }
}
