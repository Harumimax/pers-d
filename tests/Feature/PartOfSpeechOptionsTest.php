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
}
