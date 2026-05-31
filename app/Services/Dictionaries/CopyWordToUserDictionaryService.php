<?php

namespace App\Services\Dictionaries;

use App\Models\UserDictionary;
use App\Models\UserWordProgress;
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
            ]);

            $userDictionary->words()->attach($word->id);

            if (($payload['remainder_had_mistake'] ?? false) === true) {
                UserWordProgress::query()->updateOrCreate(
                    [
                        'user_id' => $userDictionary->user_id,
                        'word_id' => $word->id,
                    ],
                    [
                        'remainder_had_mistake' => true,
                    ],
                );
            }

            return $word;
        });

        return $word;
    }
}
