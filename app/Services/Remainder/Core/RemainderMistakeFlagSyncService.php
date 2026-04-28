<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\Word;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RemainderMistakeFlagSyncService
{
    public function sync(GameSession $session): void
    {
        if ($session->isDemo()) {
            return;
        }

        $this->sessionUserWordsQuery($session, false)
            ->update(['remainder_had_mistake' => true]);

        $this->sessionUserWordsQuery($session, true)
            ->where('words.remainder_had_mistake', true)
            ->update(['remainder_had_mistake' => false]);
    }

    private function sessionUserWordsQuery(GameSession $session, bool $isCorrect): Builder
    {
        return Word::query()
            ->whereIn('words.id', function ($query) use ($session, $isCorrect): void {
                $query->select('game_session_items.word_id')
                    ->from('game_session_items')
                    ->where('game_session_items.game_session_id', $session->id)
                    ->where('game_session_items.is_correct', $isCorrect);
            })
            ->whereExists(function ($query) use ($session): void {
                $query->select(DB::raw(1))
                    ->from('user_dictionary_word')
                    ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                    ->whereColumn('user_dictionary_word.word_id', 'words.id')
                    ->where('user_dictionaries.user_id', $session->user_id);
            });
    }
}
