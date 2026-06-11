<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Show;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartOfSpeechOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dictionary_page_displays_cardinal_part_of_speech_option(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSee('Cardinal')
            ->assertSee('Числительное');
    }

    public function test_remainder_page_displays_cardinal_part_of_speech_chip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('remainder'))
            ->assertOk()
            ->assertSee('Cardinal');
    }

    public function test_dictionary_page_remainder_and_tg_bot_display_collocation_option(): void
    {
        $user = User::factory()->create([
            'tg_chat_id' => '123456789',
        ]);
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSee('Collocation')
            ->assertSee('Словосочетание');

        $this->actingAs($user)
            ->get(route('remainder'))
            ->assertOk()
            ->assertSee('Collocation');

        $this->actingAs($user)
            ->get(route('tg-bot'))
            ->assertOk()
            ->assertSee('Collocation');
    }
}
