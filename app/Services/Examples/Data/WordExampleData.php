<?php

namespace App\Services\Examples\Data;

class WordExampleData
{
    public function __construct(
        public readonly string $exampleText,
        public readonly ?string $exampleTranslation,
        public readonly string $source,
        public readonly ?string $sourceExternalId = null,
    ) {
    }

    public function withTranslation(?string $translation): self
    {
        return new self(
            $this->exampleText,
            $translation,
            $this->source,
            $this->sourceExternalId,
        );
    }

    /**
     * @return array{
     *     example_text:string,
     *     example_translation:?string,
     *     source:string,
     *     source_external_id:?string
     * }
     */
    public function toPersistenceArray(): array
    {
        return [
            'example_text' => $this->exampleText,
            'example_translation' => $this->exampleTranslation,
            'source' => $this->source,
            'source_external_id' => $this->sourceExternalId,
        ];
    }
}
