<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Translation\TranslatorTextTranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TranslatorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_translator_page(): void
    {
        $this->get(route('translator.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_translator_page_and_see_menu_link_before_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('translator.index'))
            ->assertOk()
            ->assertSee('Translate short texts inside WordKeeper.')
            ->assertSee('DE')
            ->assertSee('IT')
            ->assertSee('PT')
            ->assertSeeInOrder(['TG bot', 'Translator', 'Profile']);
    }

    public function test_authenticated_user_can_translate_text_with_new_supported_languages(): void
    {
        $user = User::factory()->create();

        $translator = Mockery::mock(TranslatorTextTranslationServiceInterface::class);
        $translator->shouldReceive('translateText')
            ->once()
            ->with('Guten Morgen', 'de', 'pt')
            ->andReturn('Bom dia');

        $this->app->instance(TranslatorTextTranslationServiceInterface::class, $translator);

        $this->actingAs($user)
            ->post(route('translator.store'), [
                'source_language' => 'de',
                'target_language' => 'pt',
                'text' => 'Guten Morgen',
            ])
            ->assertOk()
            ->assertSee('Bom dia');
    }

    public function test_authenticated_user_can_translate_text(): void
    {
        $user = User::factory()->create();

        $translator = Mockery::mock(TranslatorTextTranslationServiceInterface::class);
        $translator->shouldReceive('translateText')
            ->once()
            ->with('Hello world', 'en', 'ru')
            ->andReturn('Привет, мир');

        $this->app->instance(TranslatorTextTranslationServiceInterface::class, $translator);

        $this->actingAs($user)
            ->post(route('translator.store'), [
                'source_language' => 'en',
                'target_language' => 'ru',
                'text' => 'Hello world',
            ])
            ->assertOk()
            ->assertSee('Translation result')
            ->assertSee('Привет, мир');
    }

    public function test_validation_rejects_text_longer_than_4500_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('translator.index'))
            ->post(route('translator.store'), [
                'source_language' => 'en',
                'target_language' => 'ru',
                'text' => str_repeat('a', 4501),
            ])
            ->assertRedirect(route('translator.index'))
            ->assertSessionHasErrors('text');
    }

    public function test_translation_provider_error_is_shown_as_banner(): void
    {
        $user = User::factory()->create();

        $translator = Mockery::mock(TranslatorTextTranslationServiceInterface::class);
        $translator->shouldReceive('translateText')
            ->once()
            ->andThrow(new RuntimeException('Provider is down'));

        $this->app->instance(TranslatorTextTranslationServiceInterface::class, $translator);

        $this->actingAs($user)
            ->post(route('translator.store'), [
                'source_language' => 'en',
                'target_language' => 'ru',
                'text' => 'Hello world',
            ])
            ->assertOk()
            ->assertSee('LibreTranslate is temporarily unavailable. Please try again later.');
    }

    public function test_translator_page_is_localized_to_russian(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get(route('translator.index'))
            ->assertOk()
            ->assertSee('Переводчик')
            ->assertSee('Переводите короткие тексты прямо в WordKeeper.')
            ->assertSee('Введите текст')
            ->assertSee('Результат перевода');
    }
}
