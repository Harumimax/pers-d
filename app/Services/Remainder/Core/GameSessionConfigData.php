<?php

namespace App\Services\Remainder\Core;

class GameSessionConfigData
{
    /**
     * @param array<int, int> $dictionaryIds
     * @param array<int, int> $readyDictionaryIds
     * @param array<int, string> $partsOfSpeech
     */
    public function __construct(
        public readonly string $mode,
        public readonly string $direction,
        public readonly array $dictionaryIds,
        public readonly array $readyDictionaryIds,
        public readonly array $partsOfSpeech,
        public readonly int $requestedWordsCount,
    ) {
    }

    /**
     * @param array{mode:string,direction:string,dictionary_ids?:array<int,int|string>,ready_dictionary_ids?:array<int,int|string>,parts_of_speech?:array<int,string>,words_count:int|string} $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            mode: (string) $config['mode'],
            direction: (string) $config['direction'],
            dictionaryIds: self::normalizeIntegerIds($config['dictionary_ids'] ?? []),
            readyDictionaryIds: self::normalizeIntegerIds($config['ready_dictionary_ids'] ?? []),
            partsOfSpeech: self::normalizePartsOfSpeech($config['parts_of_speech'] ?? []),
            requestedWordsCount: (int) $config['words_count'],
        );
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private static function normalizeIntegerIds(array $ids): array
    {
        return collect($ids)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $partsOfSpeech
     * @return array<int, string>
     */
    private static function normalizePartsOfSpeech(array $partsOfSpeech): array
    {
        $normalized = collect($partsOfSpeech)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->contains('all') || $normalized->isEmpty()) {
            return ['all'];
        }

        return $normalized->all();
    }

    public function usesChoiceMode(): bool
    {
        return $this->mode === 'choice';
    }
}
