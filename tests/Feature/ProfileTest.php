<?php

namespace Tests\Feature;

use App\Mail\AboutContactMessage as AboutContactMail;
use App\Models\AboutContactMessage;
use App\Models\GameSession;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_about_page(): void
    {
        $this->get('/about')
            ->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_remainder_page(): void
    {
        $this->get('/remainder')
            ->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_ready_dictionaries_page(): void
    {
        $this->get('/ready-dictionaries')
            ->assertRedirect('/login');
    }

    public function test_ready_dictionaries_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/ready-dictionaries')
            ->assertOk()
            ->assertSee('Ready dictionaries')
            ->assertSee('100 English words')
            ->assertSee('100 words')
            ->assertSee('Language')
            ->assertSee('Level')
            ->assertSee('Part of speech')
            ->assertDontSee('New Dictionary')
            ->assertSee('My Dictionaries')
            ->assertSee('Remainder');
    }

    public function test_ready_dictionaries_page_is_translated_to_russian_when_locale_is_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get('/ready-dictionaries')
            ->assertOk()
            ->assertSee('Готовые словари')
            ->assertSee('100 English words')
            ->assertSee('100 слов')
            ->assertSee('Язык')
            ->assertSee('Уровень')
            ->assertSee('Часть речи')
            ->assertSee('Мои словари')
            ->assertSee('Повторение');
    }

    public function test_about_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/about');

        $response
            ->assertOk()
            ->assertSee('About WordKeeper')
            ->assertSee('Contact form')
            ->assertSee('General statistics')
            ->assertSee('Privacy Policy and Personal Data Processing')
            ->assertSee('Cookie Policy')
            ->assertSee('Contact email')
            ->assertSee('Subject')
            ->assertSee('Message')
            ->assertSee('Send')
            ->assertSee('Clear all')
            ->assertSee('GENERAL PROVISIONS')
            ->assertSee('This Cookie Policy supplements and clarifies the general Privacy Policy and Personal Data Processing Policy')
            ->assertSee('Store part of speech as part of the game session snapshot');
    }

    public function test_about_page_is_translated_to_russian_when_locale_is_set_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['ui_locale' => 'ru'])
            ->get('/about')
            ->assertOk()
            ->assertSee('О WordKeeper')
            ->assertSee('Форма обратной связи')
            ->assertSee('Общая статистика')
            ->assertSee('Текущий функционал')
            ->assertSee('Политика конфиденциальности и обработки персональных данных')
            ->assertSee('Политика использования файлов cookie')
            ->assertSee('Готовые словари')
            ->assertSee('Повторение')
            ->assertSee('Мои словари')
            ->assertSee('Профиль')
            ->assertSee('Выйти')
            ->assertSee('О проекте');
    }

    public function test_authenticated_user_can_send_about_contact_form(): void
    {
        $user = User::factory()->create();
        Mail::fake();

        $this->actingAs($user)
            ->post(route('about.contact.store'), [
                'contact_email' => 'user@example.com',
                'subject' => 'Local delivery works',
                'message' => 'This message should be emailed and stored.',
            ])
            ->assertRedirect(route('about'))
            ->assertSessionHas('aboutContactStatus');

        $this->assertDatabaseHas('about_contact_messages', [
            'contact_email' => 'user@example.com',
            'subject' => 'Local delivery works',
            'message' => 'This message should be emailed and stored.',
            'delivery_status' => AboutContactMessage::STATUS_SENT,
        ]);

        Mail::assertSent(AboutContactMail::class, function (AboutContactMail $mail): bool {
            return $mail->hasTo((string) config('mail.about_contact_recipient'))
                && $mail->contactMessage->contact_email === 'user@example.com'
                && $mail->contactMessage->subject === 'Local delivery works';
        });
    }

    public function test_about_contact_form_validation_rejects_invalid_input(): void
    {
        $user = User::factory()->create();
        Mail::fake();

        $this->actingAs($user)
            ->from(route('about'))
            ->post(route('about.contact.store'), [
                'contact_email' => 'not-an-email',
                'subject' => '',
                'message' => '',
            ])
            ->assertRedirect(route('about'))
            ->assertSessionHasErrors(['contact_email', 'subject', 'message']);

        $this->assertDatabaseCount('about_contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_about_contact_form_marks_message_as_failed_when_delivery_throws(): void
    {
        $user = User::factory()->create();
        $recipient = (string) config('mail.about_contact_recipient');

        Mail::shouldReceive('to')
            ->once()
            ->with($recipient)
            ->andReturnSelf();
        Mail::shouldReceive('locale')
            ->once()
            ->with(app()->getLocale())
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $this->actingAs($user)
            ->from(route('about'))
            ->post(route('about.contact.store'), [
                'contact_email' => 'user@example.com',
                'subject' => 'Delivery issue',
                'message' => 'This message should end up with a failed status.',
            ])
            ->assertRedirect(route('about'))
            ->assertSessionHas('aboutContactError');

        $this->assertDatabaseHas('about_contact_messages', [
            'contact_email' => 'user@example.com',
            'subject' => 'Delivery issue',
            'message' => 'This message should end up with a failed status.',
            'delivery_status' => AboutContactMessage::STATUS_FAILED,
        ]);
    }

    public function test_about_page_displays_global_statistics_for_all_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $secondDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $thirdDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'German Basics',
            'language' => 'German',
        ]);

        $sharedWord = Word::create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $secondWord = Word::create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $firstDictionary->words()->attach([$sharedWord->id, $secondWord->id]);
        $secondDictionary->words()->attach($sharedWord->id);
        $thirdDictionary->words()->attach($secondWord->id);

        ReadyDictionary::factory()
            ->has(ReadyDictionaryWord::factory()->count(3), 'words')
            ->create([
                'name' => 'Ready English',
                'language' => 'English',
            ]);

        GameSession::create([
            'user_id' => $user->id,
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 10,
            'correct_answers' => 7,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => '2026-04-01 10:00:00',
            'finished_at' => '2026-04-01 10:10:00',
            'config_snapshot' => ['mode' => GameSession::MODE_MANUAL],
        ]);

        GameSession::create([
            'user_id' => $otherUser->id,
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 8,
            'correct_answers' => 5,
            'status' => GameSession::STATUS_ACTIVE,
            'started_at' => '2026-04-03 10:00:00',
            'finished_at' => null,
            'config_snapshot' => ['mode' => GameSession::MODE_CHOICE],
        ]);

        $this->actingAs($user)
            ->get('/about')
            ->assertOk()
            ->assertSee('Total dictionaries across all users')
            ->assertSee('Total word entries across all dictionaries')
            ->assertSee('Total game sessions played by all users')
            ->assertSee('Overall correct answers percentage across all games')
            ->assertSee('4')
            ->assertSee('7')
            ->assertSee('2')
            ->assertSee('66.7%');
    }

    public function test_about_page_shows_no_answers_fallback_when_global_game_answers_do_not_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/about')
            ->assertOk()
            ->assertSee('No answers yet');
    }

    public function test_interface_language_placeholder_route_updates_session_and_redirects_back(): void
    {
        $this->from('/')
            ->post(route('interface-language.update'), [
                'language' => 'en',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('ui_locale', 'en');
    }

    public function test_guest_can_still_change_locale_via_session_only(): void
    {
        $this->from('/')
            ->post(route('interface-language.update'), [
                'language' => 'ru',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('ui_locale', 'ru');
    }

    public function test_invalid_interface_language_is_ignored_without_error(): void
    {
        $this->from('/')
            ->post(route('interface-language.update'), [
                'language' => 'de',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('ui_locale', config('app.locale'));
    }

    public function test_authenticated_user_switching_locale_updates_preferred_locale(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->post(route('interface-language.update'), [
                'language' => 'ru',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('ui_locale', 'ru');

        $this->assertSame('ru', $user->fresh()->preferred_locale);
    }

    public function test_welcome_page_renders_language_switcher(): void
    {
        config(['app.analytics.yandex_metrika_id' => '108580029']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Save words. Practice them. Remember more.')
            ->assertSee('Product preview')
            ->assertSee('See WordKeeper in action')
            ->assertSee('Add a new word in dictionary quickly')
            ->assertSee('Review results and weak spots')
            ->assertSee('Keep words in one place')
            ->assertSee('Start building your vocabulary today')
            ->assertSee('Ru')
            ->assertSee('En')
            ->assertDontSee('mc.yandex.ru/metrika/tag.js');
    }

    public function test_welcome_page_is_translated_to_russian_when_locale_is_set(): void
    {
        $this->withSession(['ui_locale' => 'ru'])
            ->get('/')
            ->assertOk()
            ->assertSee('Персональный')
            ->assertSee('словарь иностранных слов')
            ->assertSee('Создавайте и упорядочивайте свой словарный запас')
            ->assertSee('Сохраняйте слова. Практикуйтесь. Запоминайте больше.')
            ->assertSee('Примеры интерфейса')
            ->assertSee('Посмотрите WordKeeper в работе')
            ->assertSee('Быстро добавляйте новое слово в словарь')
            ->assertSee('Просматривайте результаты и слабые места')
            ->assertSee('Сценарии использования')
            ->assertSee('Как можно использовать WordKeeper')
            ->assertSee('Храните слова в одном месте')
            ->assertSee('Начните собирать словарный запас уже сегодня')
            ->assertSee('Регистрация')
            ->assertSee('Войти');
    }

    public function test_remainder_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $englishDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'fruit',
        ]);

        $englishDictionary->words()->attach($word->id);

        $response = $this
            ->actingAs($user)
            ->get('/remainder');

        $response
            ->assertOk()
            ->assertSee('Remainder')
            ->assertSee('Configure your next repetition session')
            ->assertSee('Game type')
            ->assertSee('Translation direction')
            ->assertSee('Dictionaries')
            ->assertSee('Parts of speech')
            ->assertSee('Words count')
            ->assertSee('English Core')
            ->assertSee('English')
            ->assertSee('1 word')
            ->assertSee('Manual translation input')
            ->assertSee('Choose from 6 options')
            ->assertSee('Start')
            ->assertSee('Reset');
    }

    public function test_profile_and_about_pages_render_dictionaries_dropdown_links(): void
    {
        $user = User::factory()->create();
        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Travel Starter',
            'language' => 'English',
        ]);

        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('English Core')
            ->assertSee('Spanish Travel')
            ->assertSee('Travel Starter')
            ->assertSee(route('ready-dictionaries.show', $readyDictionary), false);

        $this->actingAs($user)
            ->get('/about')
            ->assertOk()
            ->assertSee('English Core')
            ->assertSee('Spanish Travel')
            ->assertSee('Travel Starter')
            ->assertSee(route('ready-dictionaries.show', $readyDictionary), false);
    }

    public function test_profile_page_does_not_render_dictionaries_dropdown_without_user_dictionaries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('aria-label="Your dictionaries"', false);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_displays_remainder_statistics_for_finished_sessions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        GameSession::create([
            'user_id' => $user->id,
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 10,
            'correct_answers' => 7,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => '2026-04-01 10:00:00',
            'finished_at' => '2026-04-01 10:10:00',
            'config_snapshot' => ['mode' => GameSession::MODE_MANUAL],
        ]);

        GameSession::create([
            'user_id' => $user->id,
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_RU_TO_FOREIGN,
            'total_words' => 8,
            'correct_answers' => 5,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => '2026-04-04 10:00:00',
            'finished_at' => '2026-04-04 10:08:00',
            'config_snapshot' => ['mode' => GameSession::MODE_MANUAL],
        ]);

        GameSession::create([
            'user_id' => $user->id,
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 12,
            'correct_answers' => 9,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => '2026-04-06 10:00:00',
            'finished_at' => '2026-04-06 10:09:00',
            'config_snapshot' => ['mode' => GameSession::MODE_CHOICE],
        ]);

        GameSession::create([
            'user_id' => $user->id,
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 20,
            'correct_answers' => 14,
            'status' => GameSession::STATUS_ACTIVE,
            'started_at' => '2026-04-09 10:00:00',
            'finished_at' => null,
            'config_snapshot' => ['mode' => GameSession::MODE_CHOICE],
        ]);

        GameSession::create([
            'user_id' => $otherUser->id,
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 50,
            'correct_answers' => 50,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => '2026-04-07 10:00:00',
            'finished_at' => '2026-04-07 10:20:00',
            'config_snapshot' => ['mode' => GameSession::MODE_CHOICE],
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Remainder Statistic')
            ->assertSee('Completed sessions')
            ->assertSee('3')
            ->assertSee('01 Apr 2026')
            ->assertSee('06 Apr 2026')
            ->assertSee('Manual translation input')
            ->assertSee('Foreign language to Russian')
            ->assertSee('30')
            ->assertSee('21')
            ->assertSee('9')
            ->assertSee('70%');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'preferred_locale' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('en', $user->preferred_locale);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_information_rejects_unsupported_preferred_locale(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'preferred_locale' => 'de',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('preferred_locale');

        $this->assertSame('en', $user->fresh()->preferred_locale);
    }

    public function test_profile_information_is_trimmed_and_zero_width_characters_are_removed(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => " \u{200B}Test User\u{200D} ",
                'email' => " \u{FEFF}Test@Example.com ",
                'preferred_locale' => 'ru',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('ru', $user->preferred_locale);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'preferred_locale' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('en', $user->preferred_locale);
    }

    public function test_preferred_locale_is_applied_after_login(): void
    {
        $password = 'password';
        $user = User::factory()->create([
            'email' => 'locale-user@example.com',
            'password' => $password,
            'preferred_locale' => 'ru',
        ]);

        $this->withSession(['ui_locale' => 'en'])
            ->followingRedirects()
            ->post('/login', [
                'email' => $user->email,
                'password' => $password,
            ])
            ->assertOk()
            ->assertSee('Мои словари')
            ->assertSessionHas('ui_locale', 'ru');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
