<?php

namespace Tests\Feature;

use App\Livewire\TgBot\IntervalReviewConfigurator;
use App\Models\DictionarySubscription;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TgBotIntervalReviewConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_filters_dictionaries_by_selected_language(): void
    {
        $user = User::factory()->create();

        UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'Ready English',
            'language' => 'English',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'Ready Spanish',
            'language' => 'Spanish',
        ]);

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->assertSee('English Core')
            ->assertSee('Ready English')
            ->assertDontSee('Spanish Travel')
            ->assertDontSee('Ready Spanish')
            ->set('selectedLanguage', 'Spanish')
            ->assertSee('Spanish Travel')
            ->assertSee('Ready Spanish')
            ->assertDontSee('English Core')
            ->assertDontSee('Ready English');
    }

    public function test_component_opens_dictionary_modal_and_filters_words(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $apple = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
        ]);

        $run = Word::query()->create([
            'word' => 'run',
            'translation' => 'бежать',
            'part_of_speech' => 'verb',
        ]);

        $dictionary->words()->attach([$apple->id, $run->id]);

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('openDictionary', 'user', $dictionary->id)
            ->assertSet('modalOpen', true)
            ->assertSee('apple')
            ->assertSee('run')
            ->set('modalSearch', 'apple')
            ->assertSee('apple')
            ->assertDontSee('бежать')
            ->set('modalSearch', '')
            ->set('modalPartOfSpeech', 'verb')
            ->assertSee('run')
            ->assertDontSee('apple');
    }

    public function test_component_limits_selection_to_twenty_words_and_can_remove_word(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        foreach (range(1, 21) as $index) {
            $word = Word::query()->create([
                'word' => 'word'.$index,
                'translation' => 'перевод'.$index,
            ]);

            $dictionary->words()->attach($word->id);
        }

        $component = Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->call('openDictionary', 'user', $dictionary->id)
            ->call('selectAllVisibleWords')
            ->assertSee('20/20')
            ->call('gotoModalPage', 2)
            ->call('selectAllVisibleWords')
            ->assertHasErrors(['selection_limit']);

        $firstSelectionKey = 'user:'.$dictionary->id.':1';

        $component
            ->call('removeSelectedWord', $firstSelectionKey)
            ->assertSee('19/20');
    }

    public function test_component_builds_interval_schedule_preview(): void
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
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->assertSee('Timezone for the future plan')
            ->assertSee('Europe/Moscow (UTC+03:00)')
            ->set('timezone', 'America/New_York')
            ->assertSet('timezone', 'America/New_York')
            ->call('toggleWordSelection', 'user', $dictionary->id, $word->id)
            ->set('startTime', '23:30')
            ->call('buildPlanPreview')
            ->assertSee('Schedule preview')
            ->assertSee('Collapse schedule')
            ->assertSee('Apply plan')
            ->assertSee('6 review sessions plan')
            ->assertSee('Session 1')
            ->call('collapsePlanPreview')
            ->assertDontSee('Schedule preview')
            ->assertSee('Show schedule');
    }

    public function test_component_can_open_subscribed_dictionary_words(): void
    {
        $owner = User::factory()->create();
        $subscriber = User::factory()->create();

        $dictionary = UserDictionary::query()->create([
            'user_id' => $owner->id,
            'name' => 'Shared English',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'shared',
            'translation' => 'РѕР±С‰РёР№',
            'part_of_speech' => 'adjective',
        ]);

        $dictionary->words()->attach($word->id);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        Livewire::actingAs($subscriber)
            ->test(IntervalReviewConfigurator::class, ['timezone' => 'Europe/Moscow'])
            ->assertSee('Shared English')
            ->call('openDictionary', 'user', $dictionary->id)
            ->assertSet('modalOpen', true)
            ->assertSee('shared')
            ->assertSee('РѕР±С‰РёР№');
    }
}
