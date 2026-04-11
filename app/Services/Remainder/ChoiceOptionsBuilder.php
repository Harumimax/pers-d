<?php

namespace App\Services\Remainder;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ChoiceOptionsBuilder
{
    private const OPTIONS_TARGET_COUNT = 6;
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';

    /**
     * @param Collection<int, array<string, mixed>> $itemPayloads
     * @param Collection<int, string> $availableAnswers
     * @return array{items: Collection<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function build(Collection $itemPayloads, Collection $availableAnswers): array
    {
        $normalizedAnswerMap = $availableAnswers
            ->map(function ($answer): array {
                $answer = (string) $answer;

                return [
                    'normalized' => $this->normalizeAnswer($answer),
                    'original' => $answer,
                ];
            })
            ->filter(static fn (array $answer): bool => $answer['normalized'] !== '')
            ->unique('normalized')
            ->values();

        if ($normalizedAnswerMap->count() < 2) {
            throw ValidationException::withMessages([
                'dictionary_ids' => 'Multiple choice mode requires at least 2 unique answers in the selected dictionaries and filters.',
            ]);
        }

        $warnings = [];

        if ($normalizedAnswerMap->count() < self::OPTIONS_TARGET_COUNT) {
            $warnings[] = sprintf(
                'Only %d answer %s were available for some questions because the selected dictionaries and filters did not contain enough unique answers.',
                $normalizedAnswerMap->count(),
                $normalizedAnswerMap->count() === 1 ? 'option' : 'options',
            );
        }

        $items = $itemPayloads
            ->map(function (array $item) use ($normalizedAnswerMap): array {
                $correctAnswer = (string) $item['correct_answer'];
                $normalizedCorrectAnswer = $this->normalizeAnswer($correctAnswer);

                $distractors = $normalizedAnswerMap
                    ->reject(static fn (array $candidate): bool => $candidate['normalized'] === $normalizedCorrectAnswer)
                    ->pluck('original')
                    ->shuffle()
                    ->take(self::OPTIONS_TARGET_COUNT - 1)
                    ->values();

                $options = $distractors
                    ->push($correctAnswer)
                    ->shuffle()
                    ->values()
                    ->all();

                if (count($options) < 2) {
                    throw ValidationException::withMessages([
                        'dictionary_ids' => 'Multiple choice mode requires at least 2 options for each question.',
                    ]);
                }

                $item['options_json'] = $options;

                return $item;
            });

        return [
            'items' => $items,
            'warnings' => $warnings,
        ];
    }

    private function normalizeAnswer(string $answer): string
    {
        $withoutZeroWidth = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', $answer) ?? $answer;

        return mb_strtolower(trim($withoutZeroWidth));
    }
}
