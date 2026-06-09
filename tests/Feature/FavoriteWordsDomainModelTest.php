<?php

namespace Tests\Feature;

use App\Models\FavoriteWord;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Favorites\FavoriteWordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FavoriteWordsDomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_word_relations_support_user_and_ready_dictionary_sources(): void
    {
        $user = User::factory()->create();

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $userDictionary->words()->attach($word->id);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        $userFavorite = FavoriteWord::query()->create([
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_USER,
            'source_dictionary_id' => $userDictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_USER,
            'source_word_id' => $word->id,
        ]);

        $readyFavorite = FavoriteWord::query()->create([
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_READY,
            'source_dictionary_id' => $readyDictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_READY,
            'source_word_id' => $readyWord->id,
        ]);

        $this->assertTrue($user->favoriteWords->contains($userFavorite));
        $this->assertTrue($user->favoriteWords->contains($readyFavorite));
        $this->assertTrue($word->favoriteMarks->contains($userFavorite));
        $this->assertTrue($readyWord->favoriteMarks->contains($readyFavorite));
        $this->assertSame($userDictionary->id, $userFavorite->sourceUserDictionary?->id);
        $this->assertSame($readyDictionary->id, $readyFavorite->sourceReadyDictionary?->id);
        $this->assertSame($word->id, $userFavorite->sourceWord?->id);
        $this->assertSame($readyWord->id, $readyFavorite->sourceReadyDictionaryWord?->id);
    }

    public function test_favorite_words_service_can_add_toggle_remove_and_summarize_favorites(): void
    {
        $service = app(FavoriteWordsService::class);
        $user = User::factory()->create();

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $userDictionary->words()->attach($word->id);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        $this->assertFalse($service->isUserDictionaryWordFavorite($user, $userDictionary, $word));

        $service->addUserDictionaryWord($user, $userDictionary, $word);

        $this->assertTrue($service->isUserDictionaryWordFavorite($user, $userDictionary, $word));
        $this->assertSame(1, $service->countForUser($user));

        $this->assertFalse($service->toggleUserDictionaryWord($user, $userDictionary, $word));
        $this->assertSame(0, $service->countForUser($user));

        $this->assertTrue($service->toggleUserDictionaryWord($user, $userDictionary, $word));
        $this->assertTrue($service->isUserDictionaryWordFavorite($user, $userDictionary, $word));

        $service->addReadyDictionaryWord($user, $readyDictionary, $readyWord);

        $summary = $service->virtualDictionarySummaryForUser($user);

        $this->assertSame(2, $service->countForUser($user));
        $this->assertTrue($service->isReadyDictionaryWordFavorite($user, $readyDictionary, $readyWord));
        $this->assertSame('favorites', $summary['slug']);
        $this->assertSame(2, $summary['count']);
        $this->assertTrue($summary['is_clickable']);

        $service->removeReadyDictionaryWord($user, $readyDictionary, $readyWord);

        $this->assertFalse($service->isReadyDictionaryWordFavorite($user, $readyDictionary, $readyWord));
        $this->assertSame(1, $service->countForUser($user));
    }

    public function test_favorite_words_service_rejects_word_that_does_not_belong_to_dictionary(): void
    {
        $service = app(FavoriteWordsService::class);
        $user = User::factory()->create();

        $firstDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $secondDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $secondDictionary->words()->attach($word->id);

        $this->expectException(InvalidArgumentException::class);

        $service->addUserDictionaryWord($user, $firstDictionary, $word);
    }
}
