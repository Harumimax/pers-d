<?php

namespace Database\Factories;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadyDictionaryWord>
 */
class ReadyDictionaryWordFactory extends Factory
{
    protected $model = ReadyDictionaryWord::class;

    public function definition(): array
    {
        return [
            'ready_dictionary_id' => ReadyDictionary::factory(),
            'word' => $this->faker->unique()->word(),
            'translation' => $this->faker->word(),
            'part_of_speech' => $this->faker->optional()->randomElement(PartOfSpeechCatalog::values()),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
