<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Index;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserDictionaryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_opening_dictionaries_page(): void
    {
        $response = $this->get(route('dictionaries.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_their_dictionaries_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dictionaries.index'));

        $response->assertOk();
        $response->assertSee('Your dictionaries');
    }

    public function test_user_sees_only_their_own_dictionaries(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish Basics',
        ]);

        $otherDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Private Notes',
        ]);

        $response = $this->actingAs($user)->get(route('dictionaries.index'));

        $response->assertOk();
        $response->assertSee($ownDictionary->name);
        $response->assertDontSee($otherDictionary->name);
    }

    public function test_user_can_create_dictionary_through_livewire_interface(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('name', 'Travel Words')
            ->call('createDictionary')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_dictionaries', [
            'user_id' => $user->id,
            'name' => 'Travel Words',
        ]);
    }

    public function test_user_cannot_delete_another_users_dictionary(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Hidden Dictionary',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('deleteDictionary', $foreignDictionary->id)
            ->assertForbidden();

        $this->assertDatabaseHas('user_dictionaries', [
            'id' => $foreignDictionary->id,
            'user_id' => $otherUser->id,
            'name' => 'Hidden Dictionary',
        ]);
    }
}
