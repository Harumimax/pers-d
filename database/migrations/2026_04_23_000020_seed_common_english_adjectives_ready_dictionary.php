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
                    'name' => 'The most commonly used English adjectives',
                    'language' => 'English',
                ],
                [
                    'level' => null,
                    'part_of_speech' => 'adjective',
                    'comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English adjectives')
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
                    'part_of_speech' => 'adjective',
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
                ->where('name', 'The most commonly used English adjectives')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'The most commonly used English adjectives')
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
            ['word' => 'able', 'translation' => 'способный'],
            ['word' => 'accurate', 'translation' => 'точный, аккуратный'],
            ['word' => 'actual', 'translation' => 'реальный, действующий'],
            ['word' => 'additional', 'translation' => 'дополнительный'],
            ['word' => 'afraid', 'translation' => 'испуганный'],
            ['word' => 'aggressive', 'translation' => 'агрессивный'],
            ['word' => 'alive', 'translation' => 'живой'],
            ['word' => 'angry', 'translation' => 'сердитый'],
            ['word' => 'anxious', 'translation' => 'тревожный'],
            ['word' => 'automatic', 'translation' => 'автоматический'],
            ['word' => 'available', 'translation' => 'доступный'],
            ['word' => 'aware', 'translation' => 'осведомлённый'],
            ['word' => 'bad', 'translation' => 'плохой'],
            ['word' => 'basic', 'translation' => 'основной, базовый'],
            ['word' => 'best', 'translation' => 'лучший'],
            ['word' => 'big', 'translation' => 'большой'],
            ['word' => 'boring', 'translation' => 'скучный'],
            ['word' => 'capable', 'translation' => 'способный, умелый'],
            ['word' => 'careful', 'translation' => 'осторожный'],
            ['word' => 'central', 'translation' => 'центральный'],
            ['word' => 'certain', 'translation' => 'определенный, конкретный'],
            ['word' => 'civil', 'translation' => 'гражданский'],
            ['word' => 'clear', 'translation' => 'ясный, чистый'],
            ['word' => 'close', 'translation' => 'близкий, тесный'],
            ['word' => 'cold', 'translation' => 'холодный'],
            ['word' => 'common', 'translation' => 'общий'],
            ['word' => 'confident', 'translation' => 'уверенный'],
            ['word' => 'conscious', 'translation' => 'сознательный'],
            ['word' => 'consistent', 'translation' => 'последовательный'],
            ['word' => 'critical', 'translation' => 'критический'],
            ['word' => 'cultural', 'translation' => 'культурный'],
            ['word' => 'curious', 'translation' => 'любопытный'],
            ['word' => 'current', 'translation' => 'текущий'],
            ['word' => 'dangerous', 'translation' => 'опасный'],
            ['word' => 'dark', 'translation' => 'темный'],
            ['word' => 'dead', 'translation' => 'мёртвый'],
            ['word' => 'decent', 'translation' => 'приличный, порядочный'],
            ['word' => 'democratic', 'translation' => 'демократический'],
            ['word' => 'desperate', 'translation' => 'отчаянный, безнадежный'],
            ['word' => 'different', 'translation' => 'различный'],
            ['word' => 'difficult', 'translation' => 'трудный'],
            ['word' => 'distinct', 'translation' => 'определенный, отчетливый'],
            ['word' => 'early', 'translation' => 'ранний'],
            ['word' => 'eastern', 'translation' => 'восточный'],
            ['word' => 'easy', 'translation' => 'лёгкий'],
            ['word' => 'economic', 'translation' => 'экономический'],
            ['word' => 'educational', 'translation' => 'образовательный'],
            ['word' => 'efficient', 'translation' => 'эффективный'],
            ['word' => 'emotional', 'translation' => 'эмоциональный'],
            ['word' => 'entire', 'translation' => 'целый'],
            ['word' => 'every', 'translation' => 'каждый'],
            ['word' => 'exciting', 'translation' => 'захватывающий, волнующий'],
            ['word' => 'existing', 'translation' => 'существующий'],
            ['word' => 'famous', 'translation' => 'известный'],
            ['word' => 'final', 'translation' => 'конечный'],
            ['word' => 'financial', 'translation' => 'финансовый'],
            ['word' => 'fine', 'translation' => 'прекрасный'],
            ['word' => 'foreign', 'translation' => 'иностранный'],
            ['word' => 'former', 'translation' => 'бывший'],
            ['word' => 'free', 'translation' => 'свободный'],
            ['word' => 'friendly', 'translation' => 'дружественный'],
            ['word' => 'full', 'translation' => 'полный'],
            ['word' => 'general', 'translation' => 'всеобщий, главный'],
            ['word' => 'global', 'translation' => 'глобальный'],
            ['word' => 'good', 'translation' => 'хороший'],
            ['word' => 'great', 'translation' => 'большой'],
            ['word' => 'guilty', 'translation' => 'виновный'],
            ['word' => 'happy', 'translation' => 'счастливый'],
            ['word' => 'hard', 'translation' => 'жёсткий, усердный'],
            ['word' => 'healthy', 'translation' => 'здоровый'],
            ['word' => 'high', 'translation' => 'высокий'],
            ['word' => 'historical', 'translation' => 'исторический'],
            ['word' => 'hot', 'translation' => 'горячий'],
            ['word' => 'huge', 'translation' => 'огромный'],
            ['word' => 'human', 'translation' => 'человеческий'],
            ['word' => 'hungry', 'translation' => 'голодный'],
            ['word' => 'immediate', 'translation' => 'непосредственный, срочный'],
            ['word' => 'important', 'translation' => 'важный'],
            ['word' => 'impossible', 'translation' => 'невозможный'],
            ['word' => 'impressive', 'translation' => 'выразительный, впечатляющий'],
            ['word' => 'informal', 'translation' => 'неофициальный'],
            ['word' => 'inner', 'translation' => 'внутренний'],
            ['word' => 'interesting', 'translation' => 'интересный'],
            ['word' => 'international', 'translation' => 'международный'],
            ['word' => 'known', 'translation' => 'известный'],
            ['word' => 'large', 'translation' => 'большой, широкий'],
            ['word' => 'late', 'translation' => 'поздний, последний'],
            ['word' => 'left', 'translation' => 'левый'],
            ['word' => 'legal', 'translation' => 'правовой, законный'],
            ['word' => 'likely', 'translation' => 'вероятный, возможный'],
            ['word' => 'little', 'translation' => 'маленький'],
            ['word' => 'local', 'translation' => 'местный'],
            ['word' => 'lonely', 'translation' => 'одинокий'],
            ['word' => 'long', 'translation' => 'длинный'],
            ['word' => 'low', 'translation' => 'низкий'],
            ['word' => 'lucky', 'translation' => 'счастливый, удачный'],
            ['word' => 'main', 'translation' => 'основной'],
            ['word' => 'major', 'translation' => 'главный, основной'],
            ['word' => 'medical', 'translation' => 'медицинский'],
            ['word' => 'mental', 'translation' => 'умственный'],
            ['word' => 'military', 'translation' => 'военный'],
            ['word' => 'national', 'translation' => 'национальный'],
            ['word' => 'natural', 'translation' => 'естественный'],
            ['word' => 'nervous', 'translation' => 'нервный'],
            ['word' => 'new', 'translation' => 'новый'],
            ['word' => 'nice', 'translation' => 'милый, приятный'],
            ['word' => 'numerous', 'translation' => 'многочисленный'],
            ['word' => 'obvious', 'translation' => 'очевидный'],
            ['word' => 'old', 'translation' => 'старый'],
            ['word' => 'open', 'translation' => 'открытый'],
            ['word' => 'past', 'translation' => 'прошлый, прошедший'],
            ['word' => 'personal', 'translation' => 'личный'],
            ['word' => 'physical', 'translation' => 'физический'],
            ['word' => 'pleasant', 'translation' => 'приятный, веселый'],
            ['word' => 'political', 'translation' => 'политический'],
            ['word' => 'poor', 'translation' => 'бедный'],
            ['word' => 'popular', 'translation' => 'популярный'],
            ['word' => 'possible', 'translation' => 'возможный'],
            ['word' => 'powerful', 'translation' => 'мощный'],
            ['word' => 'practical', 'translation' => 'практичный'],
            ['word' => 'private', 'translation' => 'частный'],
            ['word' => 'psychological', 'translation' => 'психологический'],
            ['word' => 'public', 'translation' => 'общественный'],
            ['word' => 'pure', 'translation' => 'чистый'],
            ['word' => 'rare', 'translation' => 'редкий'],
            ['word' => 'ready', 'translation' => 'готовый'],
            ['word' => 'real', 'translation' => 'действительный, реальный'],
            ['word' => 'realistic', 'translation' => 'реалистичный'],
            ['word' => 'reasonable', 'translation' => 'разумный'],
            ['word' => 'recent', 'translation' => 'недавний'],
            ['word' => 'relevant', 'translation' => 'подходящий'],
            ['word' => 'religious', 'translation' => 'религиозный'],
            ['word' => 'remarkable', 'translation' => 'замечательный, выдающийся'],
            ['word' => 'responsible', 'translation' => 'ответственный'],
            ['word' => 'right', 'translation' => 'прямой, верный'],
            ['word' => 'scared', 'translation' => 'испуганный'],
            ['word' => 'serious', 'translation' => 'серьезный'],
            ['word' => 'severe', 'translation' => 'суровый'],
            ['word' => 'sexual', 'translation' => 'сексуальный, половой'],
            ['word' => 'short', 'translation' => 'короткий'],
            ['word' => 'significant', 'translation' => 'значительный'],
            ['word' => 'similar', 'translation' => 'похожий'],
            ['word' => 'simple', 'translation' => 'простой'],
            ['word' => 'single', 'translation' => 'единственный'],
            ['word' => 'small', 'translation' => 'маленький'],
            ['word' => 'social', 'translation' => 'социальный'],
            ['word' => 'sorry', 'translation' => 'жалкий, печальный'],
            ['word' => 'special', 'translation' => 'специальный, особый'],
            ['word' => 'strong', 'translation' => 'сильный'],
            ['word' => 'substantial', 'translation' => 'существенный, значительный'],
            ['word' => 'successful', 'translation' => 'успешный'],
            ['word' => 'sudden', 'translation' => 'внезапный'],
            ['word' => 'sufficient', 'translation' => 'достаточный'],
            ['word' => 'suitable', 'translation' => 'подходящий'],
            ['word' => 'sure', 'translation' => 'конечно'],
            ['word' => 'suspicious', 'translation' => 'подозрительный'],
            ['word' => 'tall', 'translation' => 'высокий'],
            ['word' => 'technical', 'translation' => 'технический'],
            ['word' => 'terrible', 'translation' => 'страшный'],
            ['word' => 'tiny', 'translation' => 'крошечный'],
            ['word' => 'traditional', 'translation' => 'традиционный'],
            ['word' => 'true', 'translation' => 'верный'],
            ['word' => 'typical', 'translation' => 'типичный'],
            ['word' => 'ugly', 'translation' => 'уродливый'],
            ['word' => 'unable', 'translation' => 'неспособный'],
            ['word' => 'unfair', 'translation' => 'несправедливый'],
            ['word' => 'unhappy', 'translation' => 'несчастный'],
            ['word' => 'united', 'translation' => 'соединённый'],
            ['word' => 'unlikely', 'translation' => 'маловероятный'],
            ['word' => 'unusual', 'translation' => 'необычный'],
            ['word' => 'used', 'translation' => 'использованный'],
            ['word' => 'useful', 'translation' => 'полезный'],
            ['word' => 'various', 'translation' => 'различный'],
            ['word' => 'visible', 'translation' => 'видимый'],
            ['word' => 'weak', 'translation' => 'слабый'],
            ['word' => 'whole', 'translation' => 'целый'],
            ['word' => 'willing', 'translation' => 'готовый'],
            ['word' => 'wonderful', 'translation' => 'замечательный'],
            ['word' => 'wrong', 'translation' => 'неправильный'],
            ['word' => 'young', 'translation' => 'молодой'],
        ];
    }
};
