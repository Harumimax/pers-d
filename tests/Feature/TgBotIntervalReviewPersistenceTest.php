<?php

namespace Tests\Feature;

use App\Livewire\TgBot\IntervalReviewConfigurator;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TgBotIntervalReviewPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_interval_review_plan_and_reload_it(): void
    {
        [$user, $dictionary, $word] = $this->createUserDictionaryWithWord();

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('toggleWordSelection', 'user', $dictionary->id, $word->id)
            ->set('selectedLanguage', 'English')
            ->set('startTime', '08:45')
            ->set('enabled', true)
            ->call('applyPlan')
            ->assertSee('Interval review plan saved.')
            ->assertSet('hasPersistedPlan', true)
            ->assertSet('planPreviewVisible', true);

        $plan = TelegramIntervalReviewPlan::query()
            ->with(['words', 'sessions'])
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($plan);
        $this->assertSame(TelegramIntervalReviewPlan::STATUS_ACTIVE, $plan->status);
        $this->assertSame('English', $plan->language);
        $this->assertSame('08:45', $plan->start_time);
        $this->assertSame('Europe/Moscow', $plan->timezone);
        $this->assertSame(1, $plan->words_count);
        $this->assertCount(1, $plan->words);
        $this->assertSame('apple', $plan->words[0]->word);
        $this->assertCount(6, $plan->sessions);

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->assertSet('hasPersistedPlan', true)
            ->assertSet('enabled', true)
            ->assertSet('selectedLanguage', 'English')
            ->assertSet('startTime', '08:45')
            ->assertSee('apple')
            ->assertSee('Saved plan status')
            ->assertSee('Active');
    }

    public function test_reapplying_plan_updates_existing_plan_instead_of_creating_duplicate(): void
    {
        [$user, $dictionary, $word] = $this->createUserDictionaryWithWord();

        $component = Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('toggleWordSelection', 'user', $dictionary->id, $word->id)
            ->set('startTime', '08:45')
            ->call('applyPlan');

        $planId = TelegramIntervalReviewPlan::query()->where('user_id', $user->id)->value('id');

        $component
            ->set('startTime', '10:15')
            ->set('enabled', false)
            ->call('applyPlan')
            ->assertSee('Interval review plan saved.');

        $this->assertSame(1, TelegramIntervalReviewPlan::query()->where('user_id', $user->id)->count());

        $plan = TelegramIntervalReviewPlan::query()
            ->with('sessions')
            ->findOrFail($planId);

        $this->assertSame(TelegramIntervalReviewPlan::STATUS_PAUSED, $plan->status);
        $this->assertSame('10:15', $plan->start_time);
        $this->assertCount(6, $plan->sessions);
    }

    public function test_toggling_saved_plan_switch_pauses_and_resumes_plan(): void
    {
        [$user, $dictionary, $word] = $this->createUserDictionaryWithWord();

        $component = Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('toggleWordSelection', 'user', $dictionary->id, $word->id)
            ->call('applyPlan');

        $component
            ->set('enabled', false)
            ->assertSee('Interval review plan paused.')
            ->assertSee('Paused');

        $this->assertSame(TelegramIntervalReviewPlan::STATUS_PAUSED, TelegramIntervalReviewPlan::query()->where('user_id', $user->id)->value('status'));

        $component
            ->set('enabled', true)
            ->assertSee('Interval review plan resumed.')
            ->assertSee('Active');

        $this->assertSame(TelegramIntervalReviewPlan::STATUS_ACTIVE, TelegramIntervalReviewPlan::query()->where('user_id', $user->id)->value('status'));
    }

    public function test_reset_plan_uses_confirmation_and_deletes_saved_data(): void
    {
        [$user, $dictionary, $word] = $this->createUserDictionaryWithWord();

        $component = Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('toggleWordSelection', 'user', $dictionary->id, $word->id)
            ->call('applyPlan')
            ->call('confirmReset')
            ->assertSet('showResetConfirmation', true)
            ->assertSee('Delete the saved interval review plan?')
            ->call('resetPlan')
            ->assertSet('hasPersistedPlan', false)
            ->assertSet('showResetConfirmation', false)
            ->assertSet('selectedWords', [])
            ->assertSee('Interval review plan deleted.');

        $this->assertSame(0, TelegramIntervalReviewPlan::query()->where('user_id', $user->id)->count());
    }

    public function test_completed_plan_is_rendered_with_progress_and_no_next_session(): void
    {
        [$user, $dictionary, $word] = $this->createUserDictionaryWithWord();

        $plan = TelegramIntervalReviewPlan::query()->create([
            'user_id' => $user->id,
            'status' => TelegramIntervalReviewPlan::STATUS_COMPLETED,
            'language' => 'English',
            'start_time' => '08:45',
            'timezone' => 'Europe/Moscow',
            'words_count' => 1,
            'completed_sessions_count' => 6,
            'completed_at' => now(),
        ]);

        $plan->words()->create([
            'source_type' => 'user',
            'source_dictionary_id' => $dictionary->id,
            'source_word_id' => $word->id,
            'dictionary_name' => $dictionary->name,
            'language' => 'English',
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
            'position' => 1,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $plan->sessions()->create([
                'session_number' => $i,
                'scheduled_for' => now()->addDays($i),
                'status' => 'finished',
            ]);
        }

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->assertSet('hasPersistedPlan', true)
            ->assertSet('planStatusCode', TelegramIntervalReviewPlan::STATUS_COMPLETED)
            ->assertSet('completedSessionsCount', 6)
            ->assertSet('nextSessionLabel', null)
            ->assertSee('Completed')
            ->assertSee('6/6')
            ->assertSee('No upcoming sessions');
    }

    /**
     * @return array{0:User,1:UserDictionary,2:Word}
     */
    private function createUserDictionaryWithWord(): array
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);
        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
        ]);

        $dictionary->words()->attach($word->id);

        return [$user, $dictionary, $word];
    }
}
