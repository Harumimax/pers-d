<?php

namespace Tests\Feature;

use App\Models\DictionaryShareInvitation;
use App\Models\DictionarySubscription;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\UserWordProgress;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DictionarySharingDomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_subscriber_and_invitation_relations_are_wired(): void
    {
        $owner = User::factory()->create();
        $subscriber = User::factory()->create();

        $dictionary = UserDictionary::query()->create([
            'user_id' => $owner->id,
            'name' => 'Shared English',
            'language' => 'English',
        ]);

        $subscription = DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        $invitation = DictionaryShareInvitation::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'owner_user_id' => $owner->id,
            'target_email' => 'friend@example.com',
            'token_hash' => str_repeat('a', 64),
            'status' => DictionaryShareInvitation::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($owner->ownedDictionaries->contains($dictionary));
        $this->assertTrue($subscriber->subscribedDictionaries->contains($dictionary));
        $this->assertTrue($dictionary->subscribers->contains($subscriber));
        $this->assertTrue($dictionary->subscriptions->contains($subscription));
        $this->assertTrue($dictionary->shareInvitations->contains($invitation));
        $this->assertTrue($owner->dictionaryShareInvitations->contains($invitation));
    }

    public function test_user_word_progress_is_scoped_per_user_and_per_word(): void
    {
        $user = User::factory()->create();
        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $progress = UserWordProgress::query()->create([
            'user_id' => $user->id,
            'word_id' => $word->id,
            'remainder_had_mistake' => true,
        ]);

        $this->assertTrue($user->wordProgress->contains($progress));
        $this->assertTrue($word->userProgress->contains($progress));
        $this->assertSame($user->id, $progress->user?->id);
        $this->assertSame($word->id, $progress->word?->id);
    }
}
