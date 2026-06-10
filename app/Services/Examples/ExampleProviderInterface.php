<?php

namespace App\Services\Examples;

use App\Services\Examples\Data\WordExampleData;

interface ExampleProviderInterface
{
    /**
     * @return array<int, WordExampleData>
     */
    public function fetchExamples(
        string $word,
        string $sourceLanguage,
        string $targetLanguage,
        int $limit = 3,
    ): array;
}
