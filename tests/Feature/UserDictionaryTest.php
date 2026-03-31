<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDictionaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_dictionary(): void
    {
        $user = User::factory()->create();

        $dictionary = $user->dictionaries()->create([
            'name' => 'Spanish Basics',
        ]);

        $this->assertDatabaseHas('user_dictionaries', [
            'id' => $dictionary->id,
            'user_id' => $user->id,
            'name' => 'Spanish Basics',
        ]);
    }

    public function test_dictionary_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Travel',
        ]);

        $this->assertTrue($dictionary->user->is($user));
        $this->assertTrue($user->dictionaries->contains($dictionary));
    }

    public function test_user_cannot_have_two_dictionaries_with_the_same_name(): void
    {
        $user = User::factory()->create();

        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
        ]);

        $this->expectException(QueryException::class);

        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
        ]);
    }

    public function test_user_can_add_a_word_to_a_dictionary(): void
    {
        $dictionary = UserDictionary::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'German',
        ]);

        $word = Word::create([
            'word' => 'Haus',
            'translation' => 'House',
            'comment' => 'Common noun',
        ]);

        $dictionary->words()->attach($word);

        $this->assertDatabaseHas('user_dictionary_word', [
            'user_dictionary_id' => $dictionary->id,
            'word_id' => $word->id,
        ]);
    }

    public function test_many_to_many_relationship_works_correctly(): void
    {
        $user = User::factory()->create();
        $firstDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Daily',
        ]);
        $secondDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Advanced',
        ]);
        $word = Word::create([
            'word' => 'bonjour',
            'translation' => 'hello',
            'comment' => null,
        ]);

        $firstDictionary->words()->attach($word);
        $secondDictionary->words()->attach($word);

        $this->assertCount(1, $firstDictionary->words);
        $this->assertCount(1, $secondDictionary->words);
        $this->assertCount(2, $word->dictionaries);
        $this->assertTrue($word->dictionaries->contains($firstDictionary));
        $this->assertTrue($word->dictionaries->contains($secondDictionary));
    }
}
