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
                    'name' => 'English adverbs level C1',
                    'language' => 'English',
                ],
                [
                    'level' => 'C1',
                    'part_of_speech' => 'adverb',
                    'comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'English adverbs level C1')
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
                    'part_of_speech' => 'adverb',
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
                ->where('name', 'English adverbs level C1')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'English adverbs level C1')
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
            ['word' => 'upwards / upward', 'translation' => 'вверх / выше'],
            ['word' => 'downwards / downward', 'translation' => 'вниз / книзу'],
            ['word' => 'forth', 'translation' => 'вперед / далее'],
            ['word' => 'aboard', 'translation' => 'на борту / внутри транспортного средства'],
            ['word' => 'somewhat', 'translation' => 'отчасти / слегка / …'],
            ['word' => 'utterly', 'translation' => 'крайне / совершенно / …'],
            ['word' => 'presently', 'translation' => 'сейчас / в настоящее время'],
            ['word' => 'neatly', 'translation' => 'аккуратно / опрятно'],
            ['word' => 'reluctantly', 'translation' => 'неохотно / с неохотой / …'],
            ['word' => 'nonetheless', 'translation' => 'тем не менее / однако'],
            ['word' => 'seemingly', 'translation' => 'по-видимому / …'],
            ['word' => 'sharp', 'translation' => 'резко / остро'],
            ['word' => 'last', 'translation' => 'в последний раз / на последнем месте / …'],
            ['word' => 'dead', 'translation' => 'совершенно / замертво'],
            ['word' => 'this', 'translation' => 'так / …'],
            ['word' => 'regardless', 'translation' => 'несмотря ни на что / не обращая внимания ни на что / …'],
            ['word' => 'openly', 'translation' => 'открыто / откровенно / …'],
            ['word' => 'partially', 'translation' => 'частично / отчасти'],
            ['word' => 'officially', 'translation' => 'официально / формально'],
            ['word' => 'supposedly', 'translation' => 'предположительно / по общему мнению'],
            ['word' => 'remarkably', 'translation' => 'удивительно / необыкновенно'],
            ['word' => 'purely', 'translation' => 'чисто / исключительно'],
            ['word' => 'ultimately', 'translation' => 'в конечном счете / в конце концов'],
            ['word' => 'specifically', 'translation' => 'конкретно / особенно / …'],
            ['word' => 'hence', 'translation' => 'следовательно / отсюда'],
            ['word' => 'sufficiently', 'translation' => 'достаточно'],
            ['word' => 'repeatedly', 'translation' => 'неоднократно / несколько раз / …'],
            ['word' => 'technically', 'translation' => 'технически / формально'],
            ['word' => 'uncomfortably', 'translation' => 'неудобно / неловко'],
            ['word' => 'immensely', 'translation' => 'чрезвычайно / безмерно'],
            ['word' => 'broadly', 'translation' => 'широко / в общих чертах'],
            ['word' => 'overly', 'translation' => 'слишком / чересчур'],
            ['word' => 'accordingly', 'translation' => 'соответственно / в соответствии'],
            ['word' => 'similarly', 'translation' => 'так же / подобным образом'],
            ['word' => 'thankfully', 'translation' => 'к счастью / благодарно'],
            ['word' => 'whatsoever', 'translation' => 'абсолютно / вообще'],
            ['word' => 'formally', 'translation' => 'формально / официально'],
            ['word' => 'wisely', 'translation' => 'мудро / …'],
            ['word' => 'publicly', 'translation' => 'публично / прилюдно'],
            ['word' => 'continually', 'translation' => 'непрестанно / беспрестанно / …'],
            ['word' => 'exclusively', 'translation' => 'исключительно / только'],
            ['word' => 'unnecessarily', 'translation' => 'излишне'],
            ['word' => 'randomly', 'translation' => 'случайно / наугад'],
            ['word' => 'subsequently', 'translation' => 'впоследствии / потом'],
            ['word' => 'inevitably', 'translation' => 'неизбежно / неминуемо'],
            ['word' => 'ironically', 'translation' => 'иронично'],
            ['word' => 'modestly', 'translation' => 'умеренно / скромно'],
            ['word' => 'poorly', 'translation' => 'плохо / неудачно'],
            ['word' => 'drastically', 'translation' => 'кардинально / коренным образом'],
            ['word' => 'politically', 'translation' => 'политически / обдуманно'],
            ['word' => 'solely', 'translation' => 'исключительно / единственно'],
            ['word' => 'comparatively', 'translation' => 'сравнительно / относительно'],
            ['word' => 'incidentally', 'translation' => 'кстати / между прочим'],
            ['word' => 'commonly', 'translation' => 'повсеместно / обычно / обыкновенно'],
            ['word' => 'thereby', 'translation' => 'таким образом / посредством этого'],
            ['word' => 'mysteriously', 'translation' => 'таинственно / загадочно / …'],
            ['word' => 'indirectly', 'translation' => 'косвенно / опосредованно / …'],
            ['word' => 'rudely', 'translation' => 'грубо'],
            ['word' => 'unquestionably', 'translation' => 'бесспорно / несомненно'],
            ['word' => 'unwillingly', 'translation' => 'неохотно / против желания'],
            ['word' => 'radically', 'translation' => 'радикально / в корне'],
            ['word' => 'gross', 'translation' => 'оптом'],
            ['word' => 'historically', 'translation' => 'исторически'],
            ['word' => 'sensibly', 'translation' => 'разумно / здраво'],
            ['word' => 'notably', 'translation' => 'особенно / заметно'],
            ['word' => 'satisfactorily', 'translation' => 'удовлетворительно'],
            ['word' => 'magnificently', 'translation' => 'великолепно'],
            ['word' => 'internally', 'translation' => 'внутренне'],
            ['word' => 'namely', 'translation' => 'а именно / то есть'],
            ['word' => 'unsuccessfully', 'translation' => 'безуспешно / неуспешно'],
            ['word' => 'universally', 'translation' => 'универсально / везде'],
            ['word' => 'respectively', 'translation' => 'соответственно'],
            ['word' => 'objectively', 'translation' => 'объективно / реально'],
            ['word' => 'substantially', 'translation' => 'по существу / значительно'],
            ['word' => 'explicitly', 'translation' => 'ясно / явно'],
            ['word' => 'realistically', 'translation' => 'реалистично'],
            ['word' => 'technologically', 'translation' => 'технологически / технологично'],
            ['word' => 'informally', 'translation' => 'неформально'],
            ['word' => 'jointly', 'translation' => 'совместно / сообща'],
            ['word' => 'unreasonably', 'translation' => 'беспречинно / необоснованно'],
            ['word' => 'arguably', 'translation' => 'возможно / спорно / …'],
            ['word' => 'intensively', 'translation' => 'интенсивно / …'],
            ['word' => 'racially', 'translation' => 'расово'],
            ['word' => 'comprehensively', 'translation' => 'исчерпывающе / вразумительно'],
        ];
    }
};