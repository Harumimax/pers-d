<?php

namespace App\Services\Translation\Data;

readonly class TranslationResult
{
    /**
     * @param array<int, TranslationSuggestion> $suggestions
     */
    public function __construct(
        public array $suggestions,
    ) {
    }

    /**
     * @return array<int, array{text: string, label: string}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (TranslationSuggestion $suggestion): array => $suggestion->toArray(),
            $this->suggestions,
        );
    }
}
