<?php

namespace App\Services\Dictionaries;

use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Support\Facades\DB;

class SaveDictionaryWordService
{
    public function save(
        UserDictionary $dictionary,
        string $wordValue,
        string $translationValue,
        string $partOfSpeechValue,
        ?string $commentValue = null,
    ): Word {
        /** @var Word $word */
        $word = DB::transaction(function () use (
            $dictionary,
            $wordValue,
            $translationValue,
            $partOfSpeechValue,
            $commentValue,
        ): Word {
            $word = Word::create([
                'word' => $wordValue,
                'translation' => $translationValue,
                'part_of_speech' => $partOfSpeechValue,
                'comment' => $commentValue,
            ]);

            $dictionary->words()->attach($word->id);

            return $word;
        });

        return $word;
    }
}
