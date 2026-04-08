<?php

namespace App\Services\Translation\Data;

readonly class TranslationSuggestion
{
    public function __construct(
        public string $text,
        public string $label,
    ) {
    }

    /**
     * @return array{text: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'label' => $this->label,
        ];
    }
}
