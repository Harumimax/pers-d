<?php

namespace App\Services\ReadyDictionaries;

use App\Models\ReadyDictionary;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Database\Eloquent\Collection;

class ReadyDictionaryCatalogService
{
    /**
     * @param array{language?: mixed, level?: mixed, part_of_speech?: mixed} $filters
     * @return array{
     *     dictionaries: Collection<int, ReadyDictionary>,
     *     filterOptions: array{languages: array<int, string>, levels: array<int, string>, parts_of_speech: array<string, string>},
     *     selectedFilters: array{language: ?string, level: ?string, part_of_speech: ?string}
     * }
     */
    public function catalog(array $filters = []): array
    {
        $filterOptions = $this->filterOptions();
        $selectedFilters = $this->selectedFilters($filters, $filterOptions);

        $dictionaries = ReadyDictionary::query()
            ->withCount('words')
            ->when($selectedFilters['language'] !== null, fn ($query) => $query->where('language', $selectedFilters['language']))
            ->when($selectedFilters['level'] !== null, fn ($query) => $query->where('level', $selectedFilters['level']))
            ->when($selectedFilters['part_of_speech'] !== null, fn ($query) => $query->where('part_of_speech', $selectedFilters['part_of_speech']))
            ->orderBy('language')
            ->orderBy('name')
            ->get();

        return [
            'dictionaries' => $dictionaries,
            'filterOptions' => $filterOptions,
            'selectedFilters' => $selectedFilters,
        ];
    }

    /**
     * @return array{languages: array<int, string>, levels: array<int, string>, parts_of_speech: array<string, string>}
     */
    public function filterOptions(): array
    {
        return [
            'languages' => $this->distinctValues('language'),
            'levels' => $this->distinctValues('level'),
            'parts_of_speech' => PartOfSpeechCatalog::labels(),
        ];
    }

    /**
     * @param array{language?: mixed, level?: mixed, part_of_speech?: mixed} $filters
     * @param array{languages: array<int, string>, levels: array<int, string>, parts_of_speech: array<string, string>} $filterOptions
     * @return array{language: ?string, level: ?string, part_of_speech: ?string}
     */
    private function selectedFilters(array $filters, array $filterOptions): array
    {
        $language = $this->normalizeTextFilter($filters['language'] ?? null, $filterOptions['languages']);
        $level = $this->normalizeTextFilter($filters['level'] ?? null, $filterOptions['levels']);

        $partOfSpeech = is_string($filters['part_of_speech'] ?? null)
            ? trim((string) $filters['part_of_speech'])
            : '';

        return [
            'language' => $language,
            'level' => $level,
            'part_of_speech' => in_array($partOfSpeech, PartOfSpeechCatalog::values(), true) ? $partOfSpeech : null,
        ];
    }

    /**
     * @param array<int, string> $allowedValues
     */
    private function normalizeTextFilter(mixed $value, array $allowedValues): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return in_array($value, $allowedValues, true) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function distinctValues(string $column): array
    {
        return ReadyDictionary::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }
}
