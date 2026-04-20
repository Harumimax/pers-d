<?php

namespace Database\Factories;

use App\Models\ReadyDictionary;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadyDictionary>
 */
class ReadyDictionaryFactory extends Factory
{
    protected $model = ReadyDictionary::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'language' => $this->faker->randomElement(['English', 'Spanish']),
            'level' => $this->faker->optional()->randomElement(['A1', 'A2', 'B1']),
            'part_of_speech' => $this->faker->optional()->randomElement(PartOfSpeechCatalog::values()),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
