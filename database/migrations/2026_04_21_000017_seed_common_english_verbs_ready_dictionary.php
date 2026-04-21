<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            DB::table('ready_dictionaries')->updateOrInsert(
                [
                    'name' => 'The most commonly used English verbs',
                    'language' => 'English',
                ],
                [
                    'level' => null,
                    'part_of_speech' => 'verb',
                    'comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English verbs')
                ->where('language', 'English')
                ->value('id');

            DB::table('ready_dictionary_words')
                ->where('ready_dictionary_id', $dictionaryId)
                ->delete();

            DB::table('ready_dictionary_words')->insert(
                collect($this->words())->map(fn (array $word): array => [
                    'ready_dictionary_id' => $dictionaryId,
                    'word' => $word['word'],
                    'translation' => $word['translation'],
                    'part_of_speech' => 'verb',
                    'comment' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English verbs')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English verbs')
                ->where('language', 'English')
                ->delete();
        });
    }

    /**
     * @return array<int, array{word: string, translation: string}>
     */
    private function words(): array
    {
        return [
            ['word' => 'accept', 'translation' => 'принимать'],
            ['word' => 'add', 'translation' => 'добавлять'],
            ['word' => 'agree', 'translation' => 'соглашаться'],
            ['word' => 'allow', 'translation' => 'позволять'],
            ['word' => 'answer', 'translation' => 'отвечать'],
            ['word' => 'ask', 'translation' => 'спрашивать'],
            ['word' => 'be', 'translation' => 'быть'],
            ['word' => 'become', 'translation' => 'становиться'],
            ['word' => 'begin', 'translation' => 'начинать'],
            ['word' => 'believe', 'translation' => 'верить'],
            ['word' => 'borrow', 'translation' => 'занимать'],
            ['word' => 'break', 'translation' => 'ломать'],
            ['word' => 'bring', 'translation' => 'приносить'],
            ['word' => 'buy', 'translation' => 'покупать'],
            ['word' => 'call', 'translation' => 'называть, звонить, звать'],
            ['word' => 'can', 'translation' => 'мочь, уметь'],
            ['word' => 'cancel', 'translation' => 'отменять, аннулировать'],
            ['word' => 'change', 'translation' => 'менять, изменять'],
            ['word' => 'clean', 'translation' => 'очищать, чистить'],
            ['word' => 'close', 'translation' => 'закрывать'],
            ['word' => 'come', 'translation' => 'приходить'],
            ['word' => 'count', 'translation' => 'считать'],
            ['word' => 'cover', 'translation' => 'покрывать'],
            ['word' => 'cross', 'translation' => 'пересекать'],
            ['word' => 'cut', 'translation' => 'резать'],
            ['word' => 'dance', 'translation' => 'танцевать'],
            ['word' => 'decide', 'translation' => 'решать'],
            ['word' => 'develop', 'translation' => 'развивать'],
            ['word' => 'differ', 'translation' => 'отличаться'],
            ['word' => 'discuss', 'translation' => 'обсуждать'],
            ['word' => 'do', 'translation' => 'делать'],
            ['word' => 'draw', 'translation' => 'рисовать'],
            ['word' => 'drink', 'translation' => 'пить'],
            ['word' => 'drive', 'translation' => 'управлять, вести'],
            ['word' => 'eat', 'translation' => 'есть'],
            ['word' => 'explain', 'translation' => 'объяснять'],
            ['word' => 'fall', 'translation' => 'падать'],
            ['word' => 'fill', 'translation' => 'наполнять'],
            ['word' => 'find', 'translation' => 'находить'],
            ['word' => 'finish', 'translation' => 'заканчивать'],
            ['word' => 'fit', 'translation' => 'соответствовать'],
            ['word' => 'fix', 'translation' => 'фиксировать, ремонтировать'],
            ['word' => 'fly', 'translation' => 'летать'],
            ['word' => 'follow', 'translation' => 'следовать'],
            ['word' => 'forget', 'translation' => 'забывать'],
            ['word' => 'get', 'translation' => 'получать'],
            ['word' => 'give', 'translation' => 'давать'],
            ['word' => 'go', 'translation' => 'ходить'],
            ['word' => 'grow', 'translation' => 'расти'],
            ['word' => 'guess', 'translation' => 'догадываться'],
            ['word' => 'have', 'translation' => 'иметь'],
            ['word' => 'hear', 'translation' => 'слышать'],
            ['word' => 'help', 'translation' => 'помогать'],
            ['word' => 'hurt', 'translation' => 'обижать, повредить'],
            ['word' => 'increase', 'translation' => 'увеличивать, усиливать'],
            ['word' => 'keep', 'translation' => 'держать'],
            ['word' => 'know', 'translation' => 'знать'],
            ['word' => 'learn', 'translation' => 'учиться'],
            ['word' => 'leave', 'translation' => 'оставлять'],
            ['word' => 'let', 'translation' => 'позволять, пускать'],
            ['word' => 'lie', 'translation' => 'лгать'],
            ['word' => 'like', 'translation' => 'нравиться'],
            ['word' => 'listen', 'translation' => 'слушать'],
            ['word' => 'live', 'translation' => 'жить'],
            ['word' => 'look', 'translation' => 'смотреть'],
            ['word' => 'lose', 'translation' => 'терять'],
            ['word' => 'make', 'translation' => 'делать'],
            ['word' => 'mean', 'translation' => 'иметь в виду'],
            ['word' => 'move', 'translation' => 'двигаться'],
            ['word' => 'need', 'translation' => 'нуждаться'],
            ['word' => 'offer', 'translation' => 'предлагать'],
            ['word' => 'open', 'translation' => 'открывать'],
            ['word' => 'own', 'translation' => 'иметь, обладать'],
            ['word' => 'pay', 'translation' => 'платить'],
            ['word' => 'play', 'translation' => 'играть'],
            ['word' => 'put', 'translation' => 'класть, ставить'],
            ['word' => 'reach', 'translation' => 'достигать'],
            ['word' => 'read', 'translation' => 'читать'],
            ['word' => 'remember', 'translation' => 'помнить'],
            ['word' => 'reply', 'translation' => 'отвечать'],
            ['word' => 'run', 'translation' => 'бежать'],
            ['word' => 'say', 'translation' => 'говорить'],
            ['word' => 'see', 'translation' => 'видеть'],
            ['word' => 'sell', 'translation' => 'продавать'],
            ['word' => 'send', 'translation' => 'посылать, отправлять'],
            ['word' => 'set', 'translation' => 'устанавливать, определять'],
            ['word' => 'show', 'translation' => 'показывать'],
            ['word' => 'sign', 'translation' => 'подписывать, отмечать'],
            ['word' => 'sing', 'translation' => 'петь'],
            ['word' => 'sit', 'translation' => 'сидеть'],
            ['word' => 'sleep', 'translation' => 'спать'],
            ['word' => 'speak', 'translation' => 'говорить'],
            ['word' => 'spend', 'translation' => 'тратить'],
            ['word' => 'stand', 'translation' => 'стоять'],
            ['word' => 'start', 'translation' => 'начинать'],
            ['word' => 'stop', 'translation' => 'останавливать'],
            ['word' => 'study', 'translation' => 'изучать'],
            ['word' => 'succeed', 'translation' => 'преуспевать'],
            ['word' => 'swim', 'translation' => 'плавать'],
            ['word' => 'take', 'translation' => 'брать'],
            ['word' => 'talk', 'translation' => 'говорить'],
            ['word' => 'teach', 'translation' => 'учить'],
            ['word' => 'tell', 'translation' => 'рассказывать'],
            ['word' => 'think', 'translation' => 'думать'],
            ['word' => 'translate', 'translation' => 'переводить'],
            ['word' => 'travel', 'translation' => 'путешествовать'],
            ['word' => 'try', 'translation' => 'пытаться'],
            ['word' => 'turn', 'translation' => 'поворачивать, превращать'],
            ['word' => 'understand', 'translation' => 'понимать'],
            ['word' => 'walk', 'translation' => 'идти'],
            ['word' => 'want', 'translation' => 'хотеть'],
            ['word' => 'watch', 'translation' => 'наблюдать, смотреть'],
            ['word' => 'wear', 'translation' => 'носить'],
            ['word' => 'work', 'translation' => 'работать'],
            ['word' => 'write', 'translation' => 'писать'],
        ];
    }
};
