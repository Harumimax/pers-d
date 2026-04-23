<?php

namespace App\Services\Dictionaries;

use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Support\Facades\DB;

class CopyWordToUserDictionaryService
{
    /**
     * @param array{
     *     word:string,
     *     translation:string,
     *     part_of_speech:?string,
     *     comment:?string,
     *     remainder_had_mistake?:bool
     * } $payload
     */
    public function copy(UserDictionary $userDictionary, array $payload): Word
    {
        /** @var Word $word */
        $word = DB::transaction(function () use ($userDictionary, $payload): Word {
            $word = Word::create([
                'word' => $payload['word'],
                'translation' => $payload['translation'],
                'part_of_speech' => $payload['part_of_speech'],
                'comment' => $payload['comment'],
                'remainder_had_mistake' => $payload['remainder_had_mistake'] ?? false,
            ]);

            $userDictionary->words()->attach($word->id);

            return $word;
        });

        return $word;
    }
}
