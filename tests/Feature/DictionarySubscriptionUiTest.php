<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Index;
use App\Livewire\Dictionaries\Show;
use App\Models\DictionarySubscription;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DictionarySubscriptionUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_share_invitation_from_dictionaries_livewire_screen(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared English',
            'language' => 'English',
        ]);

        $this->actingAs($owner)
            ->get(route('dictionaries.index'))
            ->assertOk()
            ->assertSee('Share dictionary Shared English');

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openShareDictionaryModal', $dictionary->id)
            ->assertSet('sharingDictionaryId', $dictionary->id)
            ->assertSet('sharingDictionaryLabel', 'Shared English')
            ->set('sharingTargetEmail', 'friend@example.com')
            ->call('sendShareInvitation')
            ->assertHasNoErrors()
            ->assertSet('sharingDictionaryId', null)
            ->assertSet('sharingTargetEmail', '');

        $this->assertDatabaseHas('dictionary_share_invitations', [
            'user_dictionary_id' => $dictionary->id,
            'owner_user_id' => $owner->id,
            'target_email' => 'friend@example.com',
        ]);
    }

    public function test_subscribed_dictionary_is_visible_on_dictionaries_index(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
        ]);
        $subscriber = User::factory()->create([
            'email' => 'subscriber@example.com',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared Spanish',
            'language' => 'Spanish',
        ]);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        $response = $this->actingAs($subscriber)->get(route('dictionaries.index'));

        $response
            ->assertOk()
            ->assertSee('Shared Spanish')
            ->assertSee('Subscription')
            ->assertSee('Owner: owner@example.com')
            ->assertDontSee('Share dictionary Shared Spanish')
            ->assertDontSee('Edit dictionary Shared Spanish');
    }

    public function test_subscriber_can_unsubscribe_dictionary_from_index(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
        ]);
        $subscriber = User::factory()->create([
            'email' => 'subscriber@example.com',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared Spanish',
            'language' => 'Spanish',
        ]);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        Livewire::actingAs($subscriber)
            ->test(Index::class)
            ->call('confirmUnsubscribeDictionary', $dictionary->id)
            ->assertSet('pendingUnsubscribeDictionaryId', $dictionary->id)
            ->assertSet('pendingUnsubscribeDictionaryLabel', 'Shared Spanish')
            ->call('unsubscribeConfirmedDictionary')
            ->assertSet('pendingUnsubscribeDictionaryId', null);

        $this->assertDatabaseMissing('dictionary_subscriptions', [
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);
        $this->assertDatabaseHas('user_dictionaries', [
            'id' => $dictionary->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_subscriber_can_open_dictionary_in_read_only_mode(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
        ]);
        $subscriber = User::factory()->create([
            'email' => 'subscriber@example.com',
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Read Only Dictionary',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'fruit',
        ]);

        $dictionary->words()->attach($word->id);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        $response = $this->actingAs($subscriber)->get(route('dictionaries.show', $dictionary));

        $response
            ->assertOk()
            ->assertSee('Read Only Dictionary')
            ->assertSee('Subscription')
            ->assertSee('owner@example.com')
            ->assertSee('apple')
            ->assertDontSee('Add Word')
            ->assertDontSee('Edit word apple')
            ->assertDontSee('Delete word apple');
    }

    public function test_subscriber_cannot_open_create_or_edit_word_actions_through_livewire(): void
    {
        $owner = User::factory()->create();
        $subscriber = User::factory()->create();

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared Dictionary',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        Livewire::actingAs($subscriber)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('openCreateForm')
            ->assertForbidden();

        Livewire::actingAs($subscriber)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('startEditingWord', $word->id)
            ->assertForbidden();
    }

    public function test_global_search_includes_subscribed_dictionary_words(): void
    {
        $owner = User::factory()->create();
        $subscriber = User::factory()->create();

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared Search Dictionary',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'subscription alpha',
            'translation' => 'alpha',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        Livewire::actingAs($subscriber)
            ->test(Index::class)
            ->set('searchQuery', 'subscription')
            ->call('searchWords')
            ->assertSee('subscription alpha')
            ->assertSee('Shared Search Dictionary');
    }
}
