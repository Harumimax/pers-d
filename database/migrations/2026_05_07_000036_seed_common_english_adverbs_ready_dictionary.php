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
                    'name' => 'The most commonly used English Adverb',
                    'language' => 'English',
                ],
                [
                    'level' => null,
                    'part_of_speech' => 'adverb',
                    'comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English Adverb')
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
                ->where('name', 'The most commonly used English Adverb')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English Adverb')
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
            ['word' => 'above', 'translation' => 'наверху'],
            ['word' => 'actually', 'translation' => 'фактически'],
            ['word' => 'again', 'translation' => 'снова, опять'],
            ['word' => 'ahead', 'translation' => 'впереди'],
            ['word' => 'almost', 'translation' => 'почти'],
            ['word' => 'already', 'translation' => 'уже, ранее'],
            ['word' => 'also', 'translation' => 'также, тоже'],
            ['word' => 'always', 'translation' => 'всегда, постоянно'],
            ['word' => 'anywhere', 'translation' => 'везде, куда-нибудь'],
            ['word' => 'around', 'translation' => 'вокруг, около'],
            ['word' => 'away', 'translation' => 'далеко, прочь'],
            ['word' => 'before', 'translation' => 'прежде, раньше'],
            ['word' => 'below', 'translation' => 'внизу'],
            ['word' => 'both', 'translation' => 'оба'],
            ['word' => 'certainly', 'translation' => 'конечно, несомненно'],
            ['word' => 'clearly', 'translation' => 'ясно'],
            ['word' => 'close', 'translation' => 'близко'],
            ['word' => 'directly', 'translation' => 'прямо'],
            ['word' => 'down', 'translation' => 'вниз'],
            ['word' => 'early', 'translation' => 'рано'],
            ['word' => 'easily', 'translation' => 'легко'],
            ['word' => 'either', 'translation' => 'любой, также'],
            ['word' => 'else', 'translation' => 'ещё, иначе, кроме'],
            ['word' => 'enough', 'translation' => 'достаточно'],
            ['word' => 'especially', 'translation' => 'особенно'],
            ['word' => 'even', 'translation' => 'даже'],
            ['word' => 'eventually', 'translation' => 'в конечном счете'],
            ['word' => 'ever', 'translation' => 'когда-либо'],
            ['word' => 'exactly', 'translation' => 'точно'],
            ['word' => 'far', 'translation' => 'далеко, гораздо'],
            ['word' => 'fast', 'translation' => 'быстро'],
            ['word' => 'finally', 'translation' => 'в конце, окончательно'],
            ['word' => 'forward', 'translation' => 'вперед, дальше'],
            ['word' => 'hard', 'translation' => 'жестко, тяжело, сильно'],
            ['word' => 'here', 'translation' => 'здесь'],
            ['word' => 'however', 'translation' => 'однако'],
            ['word' => 'indeed', 'translation' => 'действительно'],
            ['word' => 'inside', 'translation' => 'внутри'],
            ['word' => 'instead', 'translation' => 'вместо'],
            ['word' => 'just', 'translation' => 'только'],
            ['word' => 'lately', 'translation' => 'недавно'],
            ['word' => 'later', 'translation' => 'позже'],
            ['word' => 'least', 'translation' => 'наименее, немного'],
            ['word' => 'less', 'translation' => 'менее, меньше'],
            ['word' => 'little', 'translation' => 'немного, мало'],
            ['word' => 'maybe', 'translation' => 'может быть, возможно'],
            ['word' => 'more', 'translation' => 'более'],
            ['word' => 'much', 'translation' => 'много, очень'],
            ['word' => 'nearly', 'translation' => 'почти, близко'],
            ['word' => 'never', 'translation' => 'никогда'],
            ['word' => 'now', 'translation' => 'теперь, сейчас'],
            ['word' => 'nowhere', 'translation' => 'нигде, никуда'],
            ['word' => 'often', 'translation' => 'часто'],
            ['word' => 'once', 'translation' => 'раз, когда-то'],
            ['word' => 'only', 'translation' => 'только'],
            ['word' => 'outside', 'translation' => 'снаружи'],
            ['word' => 'particularly', 'translation' => 'особенно'],
            ['word' => 'perhaps', 'translation' => 'возможно'],
            ['word' => 'probably', 'translation' => 'вероятно'],
            ['word' => 'quickly', 'translation' => 'быстро'],
            ['word' => 'quietly', 'translation' => 'тихо, спокойно'],
            ['word' => 'quite', 'translation' => 'вполне'],
            ['word' => 'rather', 'translation' => 'скорее, пожалуй'],
            ['word' => 'really', 'translation' => 'действительно'],
            ['word' => 'recently', 'translation' => 'недавно'],
            ['word' => 'scarcely', 'translation' => 'едва, с трудом'],
            ['word' => 'simply', 'translation' => 'просто'],
            ['word' => 'slowly', 'translation' => 'медленно'],
            ['word' => 'so', 'translation' => 'так, таким образом'],
            ['word' => 'sometimes', 'translation' => 'иногда'],
            ['word' => 'somewhere', 'translation' => 'где-то, где-нибудь'],
            ['word' => 'soon', 'translation' => 'скоро'],
            ['word' => 'still', 'translation' => 'спокойно, ещё'],
            ['word' => 'suddenly', 'translation' => 'вдруг, внезапно'],
            ['word' => 'then', 'translation' => 'потом, тогда'],
            ['word' => 'there', 'translation' => 'там'],
            ['word' => 'today', 'translation' => 'сегодня'],
            ['word' => 'together', 'translation' => 'вместе'],
            ['word' => 'tomorrow', 'translation' => 'завтра'],
            ['word' => 'too', 'translation' => 'слишком, очень'],
            ['word' => 'up', 'translation' => 'вверх'],
            ['word' => 'usually', 'translation' => 'обычно'],
            ['word' => 'very', 'translation' => 'очень'],
            ['word' => 'well', 'translation' => 'отлично, хорошо'],
            ['word' => 'when', 'translation' => 'когда'],
            ['word' => 'where', 'translation' => 'где'],
            ['word' => 'yesterday', 'translation' => 'вчера'],
            ['word' => 'yet', 'translation' => 'ещё, уже'],
        ];
    }
};
