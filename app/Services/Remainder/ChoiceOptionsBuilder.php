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
     * @param Collection<int, string|array{answer:string,part_of_speech:?string}> $availableAnswers
     * @return array{items: Collection<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function build(Collection $itemPayloads, Collection $availableAnswers): array
    {
        $normalizedAnswerMap = $availableAnswers
            ->map(function ($answer): array {
                if (is_array($answer)) {
                    $value = (string) ($answer['answer'] ?? '');
                    $partOfSpeech = isset($answer['part_of_speech']) && trim((string) $answer['part_of_speech']) !== ''
                        ? (string) $answer['part_of_speech']
                        : null;
                } else {
                    $value = (string) $answer;
                    $partOfSpeech = null;
                }

                return [
                    'normalized' => $this->normalizeAnswer($value),
                    'original' => $value,
                    'part_of_speech' => $partOfSpeech,
                ];
            })
            ->filter(static fn (array $answer): bool => $answer['normalized'] !== '')
            ->unique('normalized')
            ->values();

        if ($normalizedAnswerMap->count() < 2) {
            throw ValidationException::withMessages([
                'dictionary_ids' => __('remainder.messages.start.choice_requires_unique_answers'),
            ]);
        }

        $warnings = [];

        if ($normalizedAnswerMap->count() < self::OPTIONS_TARGET_COUNT) {
            $warnings[] = __('remainder.messages.start.choice_partial_warning', [
                'count' => $normalizedAnswerMap->count(),
                'option_label' => $normalizedAnswerMap->count() === 1
                    ? __('remainder.messages.start.option_label_singular')
                    : __('remainder.messages.start.option_label_plural'),
            ]);
        }

        $items = $itemPayloads
            ->map(function (array $item) use ($normalizedAnswerMap): array {
                $correctAnswer = (string) $item['correct_answer'];
                $normalizedCorrectAnswer = $this->normalizeAnswer($correctAnswer);
                $correctPartOfSpeech = isset($item['part_of_speech_snapshot']) && trim((string) $item['part_of_speech_snapshot']) !== ''
                    ? (string) $item['part_of_speech_snapshot']
                    : null;

                $allDistractors = $normalizedAnswerMap
                    ->reject(static fn (array $candidate): bool => $candidate['normalized'] === $normalizedCorrectAnswer)
                    ->values();

                $samePartOfSpeechDistractors = $correctPartOfSpeech !== null
                    ? $allDistractors
                        ->filter(static fn (array $candidate): bool => $candidate['part_of_speech'] === $correctPartOfSpeech)
                        ->values()
                    : collect();

                if ($samePartOfSpeechDistractors->count() >= self::OPTIONS_TARGET_COUNT - 1) {
                    $distractors = $samePartOfSpeechDistractors
                        ->shuffle()
                        ->take(self::OPTIONS_TARGET_COUNT - 1)
                        ->pluck('original')
                        ->values();
                } else {
                    $preferredDistractors = $samePartOfSpeechDistractors
                        ->shuffle()
                        ->take(self::OPTIONS_TARGET_COUNT - 1)
                        ->values();

                    $fallbackDistractors = $allDistractors
                        ->reject(function (array $candidate) use ($preferredDistractors): bool {
                            return $preferredDistractors->contains(
                                static fn (array $selected): bool => $selected['normalized'] === $candidate['normalized']
                            );
                        })
                        ->shuffle()
                        ->take((self::OPTIONS_TARGET_COUNT - 1) - $preferredDistractors->count())
                        ->values();

                    $distractors = $preferredDistractors
                        ->concat($fallbackDistractors)
                        ->pluck('original')
                        ->values();
                }

                $options = $distractors
                    ->push($correctAnswer)
                    ->shuffle()
                    ->values()
                    ->all();

                if (count($options) < 2) {
                    throw ValidationException::withMessages([
                        'dictionary_ids' => __('remainder.messages.start.choice_requires_question_options'),
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
