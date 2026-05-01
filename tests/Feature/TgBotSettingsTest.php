<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TgBotSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_tg_bot_page(): void
    {
        $this->get('/tg-bot')
            ->assertRedirect('/login');
    }

    public function test_tg_bot_page_shows_authorization_required_message_when_user_is_not_connected(): void
    {
        $user = User::factory()->create([
            'tg_login' => 'word_keeper',
            'tg_chat_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/tg-bot')
            ->assertOk()
            ->assertSee('Authorization required')
            ->assertSee('Telegram session settings become available only after you authorize in the bot')
            ->assertDontSee('name="timezone"', false);
    }

    public function test_tg_bot_page_renders_settings_form_for_connected_user(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $this->actingAs($user)
            ->get('/tg-bot')
            ->assertOk()
            ->assertSee('Telegram connection')
            ->assertSee('Authorized in bot')
            ->assertSee('name="timezone"', false)
            ->assertSee('UTC+')
            ->assertSee('Send random words to Telegram')
            ->assertSee('Interval review of words')
            ->assertSee('Sessions per day')
            ->assertSee('Words per session')
            ->assertSee('Select all dictionaries')
            ->assertSee('wire:id=', false)
            ->assertSee('/livewire-', false);
    }

    public function test_connected_user_can_save_telegram_settings(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $userDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Prepared Travel',
            'language' => 'English',
        ]);

        $this->actingAs($user)
            ->put(route('tg-bot.update'), [
                'timezone' => 'Europe/Moscow',
                'random_words_enabled' => '1',
                'sessions' => [
                    [
                        'send_time' => '09:15',
                        'translation_direction' => 'foreign_to_ru',
                        'words_count' => 12,
                        'part_of_speech' => ['all'],
                        'user_dictionary_ids' => [$userDictionary->id],
                        'ready_dictionary_ids' => [$readyDictionary->id],
                    ],
                    [
                        'send_time' => '18:45',
                        'translation_direction' => 'ru_to_foreign',
                        'words_count' => 7,
                        'part_of_speech' => ['verb', 'adjective'],
                        'user_dictionary_ids' => [$userDictionary->id],
                        'ready_dictionary_ids' => [],
                    ],
                ],
            ])
            ->assertRedirect(route('tg-bot'))
            ->assertSessionHas('tgBotSettingsStatus');

        $setting = TelegramSetting::query()
            ->with([
                'randomWordSessions.partsOfSpeech',
                'randomWordSessions.userDictionaries',
                'randomWordSessions.readyDictionaries',
            ])
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($setting);
        $this->assertSame('Europe/Moscow', $setting->timezone);
        $this->assertTrue($setting->random_words_enabled);
        $this->assertCount(2, $setting->randomWordSessions);

        $firstSession = $setting->randomWordSessions[0];
        $secondSession = $setting->randomWordSessions[1];

        $this->assertSame(1, $firstSession->position);
        $this->assertSame('09:15', $firstSession->send_time);
        $this->assertSame('foreign_to_ru', $firstSession->translation_direction);
        $this->assertSame(12, $firstSession->words_count);
        $this->assertCount(0, $firstSession->partsOfSpeech);
        $this->assertSame([$userDictionary->id], $firstSession->userDictionaries->pluck('id')->all());
        $this->assertSame([$readyDictionary->id], $firstSession->readyDictionaries->pluck('id')->all());

        $this->assertSame(2, $secondSession->position);
        $this->assertSame('18:45', $secondSession->send_time);
        $this->assertSame('ru_to_foreign', $secondSession->translation_direction);
        $this->assertSame(7, $secondSession->words_count);
        $this->assertEqualsCanonicalizing(['verb', 'adjective'], $secondSession->partsOfSpeech->pluck('part_of_speech')->all());
    }

    public function test_connected_user_can_toggle_random_words_status_without_saving_full_form(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $setting = TelegramSetting::query()->create([
            'user_id' => $user->id,
            'timezone' => 'Europe/Berlin',
            'random_words_enabled' => false,
        ]);

        $setting->randomWordSessions()->create([
            'position' => 1,
            'send_time' => '08:30:00',
            'translation_direction' => 'foreign_to_ru',
            'words_count' => 9,
        ]);

        $this->actingAs($user)
            ->patchJson(route('tg-bot.random-words-status.update'), [
                'random_words_enabled' => true,
            ])
            ->assertOk()
            ->assertJson([
                'random_words_enabled' => true,
                'message' => __('tg-bot.form.saved'),
            ]);

        $setting->refresh();
        $this->assertSame('Europe/Berlin', $setting->timezone);
        $this->assertTrue($setting->random_words_enabled);
        $this->assertCount(1, $setting->randomWordSessions);
        $this->assertSame('08:30:00', $setting->randomWordSessions()->first()->send_time);
        $this->assertSame(9, $setting->randomWordSessions()->first()->words_count);
    }

    public function test_connected_user_can_toggle_random_words_status_before_saving_full_settings(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $this->actingAs($user)
            ->patchJson(route('tg-bot.random-words-status.update'), [
                'random_words_enabled' => true,
            ])
            ->assertOk()
            ->assertJson([
                'random_words_enabled' => true,
            ]);

        $setting = TelegramSetting::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($setting);
        $this->assertSame('Europe/Moscow', $setting->timezone);
        $this->assertTrue($setting->random_words_enabled);
        $this->assertCount(0, $setting->randomWordSessions);
    }

    public function test_saved_tg_bot_settings_are_rendered_again_on_page_load(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $setting = TelegramSetting::query()->create([
            'user_id' => $user->id,
            'timezone' => 'Europe/Berlin',
            'random_words_enabled' => true,
        ]);

        $setting->randomWordSessions()->create([
            'position' => 1,
            'send_time' => '07:30:00',
            'translation_direction' => 'ru_to_foreign',
            'words_count' => 14,
        ]);

        $this->actingAs($user)
            ->get('/tg-bot')
            ->assertOk()
            ->assertSee('Europe/Berlin')
            ->assertSee('07:30')
            ->assertSee('\u0022words_count\u0022:14', false)
            ->assertSee('Russian to foreign language');
    }

    public function test_connected_user_cannot_save_more_than_five_sessions(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $sessions = collect(range(1, 6))
            ->map(fn (int $index): array => [
                'send_time' => sprintf('%02d:00', $index + 7),
                'translation_direction' => 'foreign_to_ru',
                'words_count' => 10,
                'part_of_speech' => ['all'],
                'user_dictionary_ids' => [$dictionary->id],
                'ready_dictionary_ids' => [],
            ])
            ->all();

        $this->actingAs($user)
            ->from(route('tg-bot'))
            ->put(route('tg-bot.update'), [
                'timezone' => 'Europe/Moscow',
                'random_words_enabled' => '1',
                'sessions' => $sessions,
            ])
            ->assertRedirect(route('tg-bot'))
            ->assertSessionHasErrors('sessions');
    }

    public function test_connected_user_cannot_attach_another_users_dictionary(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);
        $otherUser = User::factory()->create();

        $otherDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Dictionary',
            'language' => 'English',
        ]);

        $this->actingAs($user)
            ->from(route('tg-bot'))
            ->put(route('tg-bot.update'), [
                'timezone' => 'Europe/Moscow',
                'random_words_enabled' => '1',
                'sessions' => [
                    [
                        'send_time' => '10:00',
                        'translation_direction' => 'foreign_to_ru',
                        'words_count' => 10,
                        'part_of_speech' => ['all'],
                        'user_dictionary_ids' => [$otherDictionary->id],
                        'ready_dictionary_ids' => [],
                    ],
                ],
            ])
            ->assertRedirect(route('tg-bot'))
            ->assertSessionHasErrors('sessions.0.user_dictionary_ids.0');
    }

    public function test_connected_user_cannot_save_words_count_outside_allowed_range(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $this->actingAs($user)
            ->from(route('tg-bot'))
            ->put(route('tg-bot.update'), [
                'timezone' => 'Europe/Moscow',
                'random_words_enabled' => '1',
                'sessions' => [
                    [
                        'send_time' => '10:00',
                        'translation_direction' => 'foreign_to_ru',
                        'words_count' => 21,
                        'part_of_speech' => ['all'],
                        'user_dictionary_ids' => [$dictionary->id],
                        'ready_dictionary_ids' => [],
                    ],
                ],
            ])
            ->assertRedirect(route('tg-bot'))
            ->assertSessionHasErrors('sessions.0.words_count');
    }

    public function test_profile_page_shows_telegram_authorization_state_and_hint(): void
    {
        $user = User::factory()->create([
            'tg_login' => 'word_keeper',
            'tg_chat_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Telegram bot authorization:')
            ->assertSee('not authorized')
            ->assertSee('To access TG bot settings, authorize in the bot')
            ->assertSee('@WordKeeperBot_bot');
    }

    public function test_profile_page_hides_authorization_hint_when_telegram_is_connected(): void
    {
        $user = User::factory()->create([
            'tg_login' => 'word_keeper',
            'tg_chat_id' => '123456789',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Telegram bot authorization:')
            ->assertSee('authorized')
            ->assertDontSee('To access TG bot settings, authorize in the bot')
            ->assertDontSee('@WordKeeperBot_bot');
    }
}
