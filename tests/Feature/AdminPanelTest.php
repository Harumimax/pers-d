<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\ReadyDictionary;
use App\Models\TelegramGameRun;
use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_panel(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_panel_and_see_profile_link(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $managedUser = User::factory()->create([
            'email' => 'learner@example.com',
            'created_at' => '2026-07-10 09:00:00',
        ]);

        $dictionary = UserDictionary::query()->create([
            'user_id' => $managedUser->id,
            'name' => 'Italian basics',
            'language' => 'Italian',
        ]);

        $wordA = Word::query()->create([
            'word' => 'ciao',
            'translation' => 'привет',
            'part_of_speech' => 'noun',
        ]);

        $wordB = Word::query()->create([
            'word' => 'grazie',
            'translation' => 'спасибо',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach([$wordA->id, $wordB->id]);

        GameSession::query()->create([
            'user_id' => $managedUser->id,
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'total_words' => 10,
            'correct_answers' => 8,
            'status' => GameSession::STATUS_FINISHED,
            'started_at' => now()->subDay(),
            'finished_at' => now()->subDay()->addMinutes(10),
            'config_snapshot' => [],
        ]);

        $telegramSetting = TelegramSetting::query()->create([
            'user_id' => $managedUser->id,
            'timezone' => 'Europe/Moscow',
            'random_words_enabled' => true,
        ]);

        $telegramSession = TelegramRandomWordSession::query()->create([
            'telegram_setting_id' => $telegramSetting->id,
            'position' => 1,
            'send_time' => '09:00:00',
            'translation_direction' => 'foreign_to_ru',
        ]);

        TelegramGameRun::query()->create([
            'user_id' => $managedUser->id,
            'telegram_setting_id' => $telegramSetting->id,
            'telegram_random_word_session_id' => $telegramSession->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 5,
            'correct_answers' => 3,
            'incorrect_answers' => 2,
            'status' => 'finished',
            'scheduled_for' => now()->subHours(12),
            'started_at' => now()->subHours(12),
            'finished_at' => now()->subHours(11),
            'config_snapshot' => [],
        ]);

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Admin Panel');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('learner@example.com')
            ->assertSee('Italian basics')
            ->assertSee('Italian')
            ->assertSee('Prepared Dictionaries')
            ->assertSee('2')
            ->assertSee('73%');
    }

    public function test_admin_panel_filters_users_and_dictionaries(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $userA = User::factory()->create(['email' => 'anna@example.com']);
        $userB = User::factory()->create(['email' => 'boris@example.com']);

        UserDictionary::query()->create([
            'user_id' => $userA->id,
            'name' => 'Travel pack',
            'language' => 'English',
        ]);

        UserDictionary::query()->create([
            'user_id' => $userB->id,
            'name' => 'Office set',
            'language' => 'German',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'Italian starter',
            'language' => 'Italian',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'German starter',
            'language' => 'German',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin?user_email=anna&user_dictionary_name=Travel&ready_dictionary_name=Italian');

        $response
            ->assertOk()
            ->assertSee('anna@example.com')
            ->assertDontSee('boris@example.com')
            ->assertSee('Travel pack')
            ->assertDontSee('Office set')
            ->assertSee('Italian starter');

        $readySection = Str::after($response->getContent(), 'id="admin-ready-dictionaries-section"');

        $this->assertStringContainsString('Italian starter', $readySection);
        $this->assertStringNotContainsString('German starter', $readySection);
    }

    public function test_admin_panel_supports_sorting_and_open_links(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $alphaUser = User::factory()->create([
            'email' => 'alpha@example.com',
            'created_at' => '2026-07-20 10:00:00',
        ]);
        $zetaUser = User::factory()->create([
            'email' => 'zeta@example.com',
            'created_at' => '2026-07-19 10:00:00',
        ]);

        $alphaDictionary = UserDictionary::query()->create([
            'user_id' => $alphaUser->id,
            'name' => 'Alpha dictionary',
            'language' => 'English',
            'created_at' => '2026-07-20 11:00:00',
            'updated_at' => '2026-07-20 11:00:00',
        ]);

        $zetaDictionary = UserDictionary::query()->create([
            'user_id' => $zetaUser->id,
            'name' => 'Zeta dictionary',
            'language' => 'Spanish',
            'created_at' => '2026-07-19 11:00:00',
            'updated_at' => '2026-07-19 11:00:00',
        ]);

        $smallWord = Word::query()->create([
            'word' => 'one',
            'translation' => 'один',
            'part_of_speech' => 'noun',
        ]);
        $largeWordA = Word::query()->create([
            'word' => 'two',
            'translation' => 'два',
            'part_of_speech' => 'noun',
        ]);
        $largeWordB = Word::query()->create([
            'word' => 'three',
            'translation' => 'три',
            'part_of_speech' => 'noun',
        ]);

        $alphaDictionary->words()->attach([$largeWordA->id, $largeWordB->id]);
        $zetaDictionary->words()->attach([$smallWord->id]);

        $olderReadyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Older ready dictionary',
            'created_at' => '2026-07-18 09:00:00',
            'updated_at' => '2026-07-18 09:00:00',
        ]);
        $newerReadyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Newer ready dictionary',
            'created_at' => '2026-07-20 09:00:00',
            'updated_at' => '2026-07-20 09:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin?users_sort=email&users_direction=desc&dictionaries_sort=word_count&dictionaries_direction=desc&ready_dictionaries_sort=created_at&ready_dictionaries_direction=asc');

        $response->assertOk()
            ->assertSee(route('dictionaries.show', $alphaDictionary), false)
            ->assertSee(route('ready-dictionaries.show', $olderReadyDictionary), false);

        $content = $response->getContent();
        $usersSection = Str::before(Str::after($content, 'id="admin-users-section"'), 'id="admin-user-dictionaries-section"');
        $dictionariesSection = Str::before(Str::after($content, 'id="admin-user-dictionaries-section"'), 'id="admin-ready-dictionaries-section"');
        $readySection = Str::after($content, 'id="admin-ready-dictionaries-section"');

        $this->assertTrue(strpos($usersSection, 'zeta@example.com') < strpos($usersSection, 'alpha@example.com'));
        $this->assertTrue(strpos($dictionariesSection, 'Alpha dictionary') < strpos($dictionariesSection, 'Zeta dictionary'));
        $this->assertTrue(strpos($readySection, 'Older ready dictionary') < strpos($readySection, 'Newer ready dictionary'));

        $this->actingAs($admin)
            ->get(route('dictionaries.show', $alphaDictionary))
            ->assertOk();
    }

    public function test_admin_panel_uses_separate_paginators_with_twenty_items_per_page(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        foreach (range(1, 21) as $index) {
            $user = User::factory()->create([
                'email' => sprintf('member%02d@example.com', $index),
            ]);

            UserDictionary::query()->create([
                'user_id' => $user->id,
                'name' => sprintf('Dictionary %02d', $index),
                'language' => 'English',
            ]);
        }

        $response = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $content = $response->getContent();
        $usersSection = Str::before(Str::after($content, 'id="admin-users-section"'), 'id="admin-user-dictionaries-section"');
        $dictionariesSection = Str::before(Str::after($content, 'id="admin-user-dictionaries-section"'), 'id="admin-ready-dictionaries-section"');

        $this->assertStringContainsString('member01@example.com', $usersSection);
        $this->assertStringContainsString('member19@example.com', $usersSection);
        $this->assertStringNotContainsString('member20@example.com', $usersSection);
        $this->assertStringContainsString('Dictionary 01', $dictionariesSection);
        $this->assertStringContainsString('Dictionary 20', $dictionariesSection);
        $this->assertStringNotContainsString('Dictionary 21', $dictionariesSection);

        $this->actingAs($admin)
            ->get('/admin?users_page=2&dictionaries_page=2')
            ->assertOk()
            ->assertSee('member20@example.com')
            ->assertSee('member21@example.com')
            ->assertSee('Dictionary 21');
    }

    public function test_admin_can_delete_user_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $user = User::factory()->create([
            'email' => 'delete-me@example.com',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_protected_admin_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.index'));

        $response->assertSessionHas('admin_flash', [
            'type' => 'warning',
            'message' => __('admin.flash.admin_user_protected'),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'email' => 'harumimax@gmail.com',
        ]);
    }

    public function test_admin_can_delete_dictionary(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $owner = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $owner->id,
            'name' => 'Delete dictionary',
            'language' => 'Spanish',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.dictionaries.destroy', $dictionary))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('user_dictionaries', [
            'id' => $dictionary->id,
        ]);
    }

    public function test_admin_can_delete_ready_dictionary(): void
    {
        $admin = User::factory()->create([
            'email' => 'harumimax@gmail.com',
        ]);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready delete',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.ready-dictionaries.destroy', $readyDictionary))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('ready_dictionaries', [
            'id' => $readyDictionary->id,
        ]);
    }
}
