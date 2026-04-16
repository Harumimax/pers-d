<?php

namespace App\Services\About;

use App\Models\GameSession;
use App\Models\UserDictionary;
use Illuminate\Support\Facades\DB;

class GlobalStatisticsService
{
    /**
     * @return array<string, int|float|null>
     */
    public function summary(): array
    {
        $dictionariesCount = UserDictionary::query()->count();
        $wordEntriesCount = (int) DB::table('user_dictionary_word')->count();
        $sessionsCount = GameSession::query()->count();

        $answersSummary = GameSession::query()
            ->where('total_words', '>', 0)
            ->selectRaw('COALESCE(SUM(total_words), 0) as total_words')
            ->selectRaw('COALESCE(SUM(correct_answers), 0) as correct_answers')
            ->first();

        $totalWords = (int) ($answersSummary?->total_words ?? 0);
        $correctAnswers = (int) ($answersSummary?->correct_answers ?? 0);
        $accuracyPercentage = $totalWords > 0
            ? round(($correctAnswers / $totalWords) * 100, 1)
            : null;

        return [
            'dictionaries_count' => $dictionariesCount,
            'word_entries_count' => $wordEntriesCount,
            'sessions_count' => $sessionsCount,
            'accuracy_percentage' => $accuracyPercentage,
        ];
    }
}
