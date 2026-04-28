<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use Illuminate\Validation\ValidationException;

class GameAnswerEvaluator
{
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';

    /**
     * @return array{0: string, 1: bool}
     */
    public function evaluate(GameSession $gameSession, GameSessionItem $currentItem, string $answer): array
    {
        if ($gameSession->mode === GameSession::MODE_CHOICE) {
            $selectedChoice = $this->sanitizeAnswer($answer);

            if ($selectedChoice === '') {
                throw ValidationException::withMessages([
                    'selectedChoice' => __('remainder.messages.play.choose_option'),
                ]);
            }

            $options = collect($currentItem->options_json ?? [])
                ->map(static fn ($option): string => (string) $option)
                ->values();

            if (! $options->contains($selectedChoice)) {
                throw ValidationException::withMessages([
                    'selectedChoice' => __('remainder.messages.play.choose_available_option'),
                ]);
            }

            return [
                $selectedChoice,
                $selectedChoice === $currentItem->correct_answer,
            ];
        }

        $sanitizedAnswer = $this->sanitizeAnswer($answer);

        if ($sanitizedAnswer === '') {
            throw ValidationException::withMessages([
                'answer' => __('remainder.messages.play.enter_translation'),
            ]);
        }

        return [
            $sanitizedAnswer,
            $this->isManualAnswerCorrect($sanitizedAnswer, $currentItem->correct_answer),
        ];
    }

    private function sanitizeAnswer(string $answer): string
    {
        $withoutZeroWidth = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', $answer) ?? $answer;

        return trim($withoutZeroWidth);
    }

    private function normalizeForComparison(string $value): string
    {
        return mb_strtolower($this->sanitizeAnswer($value));
    }

    private function isManualAnswerCorrect(string $answer, string $correctAnswer): bool
    {
        $normalizedAnswer = $this->normalizeForComparison($answer);

        if ($normalizedAnswer === $this->normalizeForComparison($correctAnswer)) {
            return true;
        }

        return collect(preg_split('/[,;]+/u', $correctAnswer) ?: [])
            ->map(fn (string $answerOption): string => $this->normalizeForComparison($answerOption))
            ->filter()
            ->contains($normalizedAnswer);
    }
}
