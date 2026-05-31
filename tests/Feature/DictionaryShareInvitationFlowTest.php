<?php

namespace Tests\Feature;

use App\Models\DictionaryShareInvitation;
use App\Models\DictionarySubscription;
use App\Models\User;
use App\Models\UserDictionary;
use App\Notifications\DictionarySubscriptions\DictionaryShareInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DictionaryShareInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_invitation_for_existing_user(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        User::factory()->create(['email' => 'friend@example.com']);
        $dictionary = $this->createDictionary($owner);

        $response = $this->actingAs($owner)->post(
            route('dictionary-share-invitations.store', $dictionary),
            ['target_email' => 'Friend@example.com '],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('dictionary_share_invitations', [
            'user_dictionary_id' => $dictionary->id,
            'owner_user_id' => $owner->id,
            'target_email' => 'friend@example.com',
            'status' => DictionaryShareInvitation::STATUS_PENDING,
        ]);

        Notification::assertCount(1);
    }

    public function test_owner_can_create_invitation_for_email_without_account(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $dictionary = $this->createDictionary($owner);

        $this->actingAs($owner)->post(
            route('dictionary-share-invitations.store', $dictionary),
            ['target_email' => 'new-friend@example.com'],
        )->assertRedirect();

        $this->assertDatabaseHas('dictionary_share_invitations', [
            'target_email' => 'new-friend@example.com',
            'status' => DictionaryShareInvitation::STATUS_PENDING,
        ]);

        Notification::assertCount(1);
    }

    public function test_non_owner_cannot_create_dictionary_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $dictionary = $this->createDictionary($owner);

        $this->actingAs($intruder)->post(
            route('dictionary-share-invitations.store', $dictionary),
            ['target_email' => 'friend@example.com'],
        )->assertForbidden();

        $this->assertDatabaseCount('dictionary_share_invitations', 0);
        Notification::assertNothingSent();
    }

    public function test_guest_can_open_invitation_page_and_session_stores_intended_url(): void
    {
        $dictionary = $this->createDictionary(User::factory()->create(['email' => 'owner@example.com']));
        $invitation = $this->createInvitation($dictionary, 'friend@example.com');

        $response = $this->get(route('dictionary-subscriptions.show', ['token' => $invitation['raw_token']]));

        $response
            ->assertOk()
            ->assertSee('owner@example.com', false)
            ->assertSee('Shared dictionary', false)
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);

        $this->assertSame(
            route('dictionary-subscriptions.show', ['token' => $invitation['raw_token']]),
            session('url.intended'),
        );
    }

    public function test_user_with_matching_email_can_accept_invitation(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $subscriber = User::factory()->create(['email' => 'friend@example.com']);
        $dictionary = $this->createDictionary($owner);
        $invitation = $this->createInvitation($dictionary, 'friend@example.com');

        $response = $this->actingAs($subscriber)->post(
            route('dictionary-subscriptions.accept', ['token' => $invitation['raw_token']]),
        );

        $response->assertRedirect(route('dictionaries.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('dictionary_subscriptions', [
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);
        $this->assertDatabaseHas('dictionary_share_invitations', [
            'id' => $invitation['invitation']->id,
            'status' => DictionaryShareInvitation::STATUS_ACCEPTED,
        ]);
    }

    public function test_repeat_acceptance_does_not_duplicate_subscription(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $subscriber = User::factory()->create(['email' => 'friend@example.com']);
        $dictionary = $this->createDictionary($owner);
        $invitation = $this->createInvitation($dictionary, 'friend@example.com');

        $this->actingAs($subscriber)->post(
            route('dictionary-subscriptions.accept', ['token' => $invitation['raw_token']]),
        )->assertRedirect(route('dictionaries.index'));

        $this->actingAs($subscriber)->post(
            route('dictionary-subscriptions.accept', ['token' => $invitation['raw_token']]),
        )->assertRedirect(route('dictionaries.index'));

        $this->assertSame(1, DictionarySubscription::query()->count());
    }

    public function test_user_with_different_email_cannot_accept_invitation(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
        $dictionary = $this->createDictionary($owner);
        $invitation = $this->createInvitation($dictionary, 'friend@example.com');

        $response = $this->actingAs($wrongUser)->post(
            route('dictionary-subscriptions.accept', ['token' => $invitation['raw_token']]),
        );

        $response->assertRedirect(route('dictionary-subscriptions.show', ['token' => $invitation['raw_token']]));
        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseCount('dictionary_subscriptions', 0);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $subscriber = User::factory()->create(['email' => 'friend@example.com']);
        $dictionary = $this->createDictionary($owner);
        $invitation = $this->createInvitation($dictionary, 'friend@example.com', now()->subDay());

        $response = $this->actingAs($subscriber)->post(
            route('dictionary-subscriptions.accept', ['token' => $invitation['raw_token']]),
        );

        $response->assertRedirect(route('dictionary-subscriptions.show', ['token' => $invitation['raw_token']]));
        $response->assertSessionHasErrors('invitation');

        $this->assertDatabaseHas('dictionary_share_invitations', [
            'id' => $invitation['invitation']->id,
            'status' => DictionaryShareInvitation::STATUS_EXPIRED,
        ]);
        $this->assertDatabaseCount('dictionary_subscriptions', 0);
    }

    public function test_registration_after_opening_invitation_returns_user_back_to_invitation_page(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $dictionary = $this->createDictionary($owner);
        $invitation = $this->createInvitation($dictionary, 'friend@example.com');
        $invitationUrl = route('dictionary-subscriptions.show', ['token' => $invitation['raw_token']]);

        $this->get($invitationUrl)->assertOk();

        $response = $this->post('/register', [
            'name' => 'Friend',
            'email' => 'friend@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect($invitationUrl);
    }

    private function createDictionary(User $owner): UserDictionary
    {
        return UserDictionary::query()->create([
            'user_id' => $owner->id,
            'name' => 'Shared dictionary',
            'language' => 'English',
        ]);
    }

    /**
     * @return array{invitation:DictionaryShareInvitation,raw_token:string}
     */
    private function createInvitation(UserDictionary $dictionary, string $targetEmail, ?\Illuminate\Support\Carbon $expiresAt = null): array
    {
        $rawToken = 'token-'.bin2hex(random_bytes(12));

        $invitation = DictionaryShareInvitation::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'owner_user_id' => $dictionary->user_id,
            'target_email' => mb_strtolower($targetEmail),
            'token_hash' => hash('sha256', $rawToken),
            'status' => DictionaryShareInvitation::STATUS_PENDING,
            'expires_at' => $expiresAt ?? now()->addDays(7),
        ]);

        return [
            'invitation' => $invitation,
            'raw_token' => $rawToken,
        ];
    }
}
