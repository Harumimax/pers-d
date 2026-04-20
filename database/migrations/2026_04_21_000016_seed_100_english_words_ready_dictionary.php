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
                    'name' => '100 English words',
                    'language' => 'English',
                ],
                [
                    'level' => null,
                    'part_of_speech' => null,
                    'comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', '100 English words')
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
                    'part_of_speech' => $word['part_of_speech'],
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
                ->where('name', '100 English words')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', '100 English words')
                ->where('language', 'English')
                ->delete();
        });
    }

    /**
     * @return array<int, array{word: string, translation: string, part_of_speech: string}>
     */
    private function words(): array
    {
        return [
            ['word' => 'Abandon', 'translation' => 'Покидать, оставлять', 'part_of_speech' => 'verb'],
            ['word' => 'Abundant', 'translation' => 'Обильный, избыточный', 'part_of_speech' => 'adjective'],
            ['word' => 'Accomplish', 'translation' => 'Выполнять, достигать', 'part_of_speech' => 'verb'],
            ['word' => 'Accurate', 'translation' => 'Точный', 'part_of_speech' => 'adjective'],
            ['word' => 'Acknowledge', 'translation' => 'Признавать', 'part_of_speech' => 'verb'],
            ['word' => 'Adapt', 'translation' => 'Приспосабливаться', 'part_of_speech' => 'verb'],
            ['word' => 'Adequate', 'translation' => 'Достаточный, соответствующий', 'part_of_speech' => 'adjective'],
            ['word' => 'Advocate', 'translation' => 'Защищать, поддерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Affect', 'translation' => 'Влиять', 'part_of_speech' => 'verb'],
            ['word' => 'Ambiguous', 'translation' => 'Двусмысленный, неоднозначный', 'part_of_speech' => 'adjective'],
            ['word' => 'Anticipate', 'translation' => 'Ожидать, предвидеть', 'part_of_speech' => 'verb'],
            ['word' => 'Apparent', 'translation' => 'Очевидный, явный', 'part_of_speech' => 'adjective'],
            ['word' => 'Appeal', 'translation' => 'Привлекать', 'part_of_speech' => 'verb'],
            ['word' => 'Appropriate', 'translation' => 'Подходящий', 'part_of_speech' => 'adjective'],
            ['word' => 'Arbitrary', 'translation' => 'Произвольный', 'part_of_speech' => 'adjective'],
            ['word' => 'Assert', 'translation' => 'Утверждать', 'part_of_speech' => 'verb'],
            ['word' => 'Assess', 'translation' => 'Оценивать', 'part_of_speech' => 'verb'],
            ['word' => 'Assign', 'translation' => 'Назначать, присваивать', 'part_of_speech' => 'verb'],
            ['word' => 'Assume', 'translation' => 'Предполагать', 'part_of_speech' => 'verb'],
            ['word' => 'Assure', 'translation' => 'Уверять', 'part_of_speech' => 'verb'],
            ['word' => 'Attach', 'translation' => 'Прикреплять', 'part_of_speech' => 'verb'],
            ['word' => 'Attain', 'translation' => 'Достигать', 'part_of_speech' => 'verb'],
            ['word' => 'Aware', 'translation' => 'Осведомленный', 'part_of_speech' => 'adjective'],
            ['word' => 'Benevolent', 'translation' => 'Доброжелательный', 'part_of_speech' => 'adjective'],
            ['word' => 'Bias', 'translation' => 'Предвзятость', 'part_of_speech' => 'noun'],
            ['word' => 'Bizarre', 'translation' => 'Странный', 'part_of_speech' => 'adjective'],
            ['word' => 'Clarify', 'translation' => 'Уточнять, прояснять', 'part_of_speech' => 'verb'],
            ['word' => 'Coherent', 'translation' => 'Последовательный, связный', 'part_of_speech' => 'adjective'],
            ['word' => 'Commitment', 'translation' => 'Обязательство', 'part_of_speech' => 'noun'],
            ['word' => 'Compel', 'translation' => 'Заставлять, вынуждать', 'part_of_speech' => 'verb'],
            ['word' => 'Comprehensive', 'translation' => 'Всеобъемлющий', 'part_of_speech' => 'adjective'],
            ['word' => 'Conceive', 'translation' => 'Задумать, представить', 'part_of_speech' => 'verb'],
            ['word' => 'Conclude', 'translation' => 'Заключать, делать вывод', 'part_of_speech' => 'verb'],
            ['word' => 'Condemn', 'translation' => 'Осуждать', 'part_of_speech' => 'verb'],
            ['word' => 'Confine', 'translation' => 'Ограничивать', 'part_of_speech' => 'verb'],
            ['word' => 'Consent', 'translation' => 'Согласие', 'part_of_speech' => 'noun'],
            ['word' => 'Consistent', 'translation' => 'Последовательный', 'part_of_speech' => 'adjective'],
            ['word' => 'Constrain', 'translation' => 'Ограничивать, сдерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Contemporary', 'translation' => 'Современный', 'part_of_speech' => 'adjective'],
            ['word' => 'Contradict', 'translation' => 'Противоречить', 'part_of_speech' => 'verb'],
            ['word' => 'Convey', 'translation' => 'Передавать', 'part_of_speech' => 'verb'],
            ['word' => 'Convince', 'translation' => 'Убеждать', 'part_of_speech' => 'verb'],
            ['word' => 'Core', 'translation' => 'Основной', 'part_of_speech' => 'adjective'],
            ['word' => 'Correspond', 'translation' => 'Соответствовать', 'part_of_speech' => 'verb'],
            ['word' => 'Crucial', 'translation' => 'Ключевой, решающий', 'part_of_speech' => 'adjective'],
            ['word' => 'Deny', 'translation' => 'Отрицать', 'part_of_speech' => 'verb'],
            ['word' => 'Derive', 'translation' => 'Получать, извлекать', 'part_of_speech' => 'verb'],
            ['word' => 'Devote', 'translation' => 'Посвящать', 'part_of_speech' => 'verb'],
            ['word' => 'Diminish', 'translation' => 'Уменьшать', 'part_of_speech' => 'verb'],
            ['word' => 'Distinct', 'translation' => 'Отчетливый, различимый', 'part_of_speech' => 'adjective'],
            ['word' => 'Diverse', 'translation' => 'Разнообразный', 'part_of_speech' => 'adjective'],
            ['word' => 'Emerge', 'translation' => 'Появляться', 'part_of_speech' => 'verb'],
            ['word' => 'Enhance', 'translation' => 'Улучшать, усиливать', 'part_of_speech' => 'verb'],
            ['word' => 'Ensure', 'translation' => 'Обеспечивать, гарантировать', 'part_of_speech' => 'verb'],
            ['word' => 'Entity', 'translation' => 'Сущность, объект', 'part_of_speech' => 'noun'],
            ['word' => 'Exaggerate', 'translation' => 'Преувеличивать', 'part_of_speech' => 'verb'],
            ['word' => 'Explicit', 'translation' => 'Явный, откровенный', 'part_of_speech' => 'adjective'],
            ['word' => 'Framework', 'translation' => 'Структура, рамки', 'part_of_speech' => 'noun'],
            ['word' => 'Generate', 'translation' => 'Производить, генерировать', 'part_of_speech' => 'verb'],
            ['word' => 'Grant', 'translation' => 'Предоставлять', 'part_of_speech' => 'verb'],
            ['word' => 'Hence', 'translation' => 'Следовательно', 'part_of_speech' => 'adverb'],
            ['word' => 'Hypothesis', 'translation' => 'Гипотеза', 'part_of_speech' => 'noun'],
            ['word' => 'Imply', 'translation' => 'Подразумевать', 'part_of_speech' => 'verb'],
            ['word' => 'Incentive', 'translation' => 'Стимул', 'part_of_speech' => 'noun'],
            ['word' => 'Incorporate', 'translation' => 'Включать в себя', 'part_of_speech' => 'verb'],
            ['word' => 'Indicate', 'translation' => 'Указывать', 'part_of_speech' => 'verb'],
            ['word' => 'Inevitable', 'translation' => 'Неизбежный', 'part_of_speech' => 'adjective'],
            ['word' => 'Infer', 'translation' => 'Делать вывод', 'part_of_speech' => 'verb'],
            ['word' => 'Inhibit', 'translation' => 'Сдерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Initiate', 'translation' => 'Начинать', 'part_of_speech' => 'verb'],
            ['word' => 'Insight', 'translation' => 'Проницательность', 'part_of_speech' => 'noun'],
            ['word' => 'Integral', 'translation' => 'Неотъемлемый', 'part_of_speech' => 'adjective'],
            ['word' => 'Intense', 'translation' => 'Интенсивный', 'part_of_speech' => 'adjective'],
            ['word' => 'Intervene', 'translation' => 'Вмешиваться', 'part_of_speech' => 'verb'],
            ['word' => 'Justify', 'translation' => 'Оправдывать', 'part_of_speech' => 'verb'],
            ['word' => 'Legitimate', 'translation' => 'Законный', 'part_of_speech' => 'adjective'],
            ['word' => 'Manipulate', 'translation' => 'Манипулировать', 'part_of_speech' => 'verb'],
            ['word' => 'Mature', 'translation' => 'Зрелый', 'part_of_speech' => 'adjective'],
            ['word' => 'Neglect', 'translation' => 'Пренебрегать', 'part_of_speech' => 'verb'],
            ['word' => 'Notion', 'translation' => 'Понятие, представление', 'part_of_speech' => 'noun'],
            ['word' => 'Obtain', 'translation' => 'Получать', 'part_of_speech' => 'verb'],
            ['word' => 'Obvious', 'translation' => 'Очевидный', 'part_of_speech' => 'adjective'],
            ['word' => 'Outcome', 'translation' => 'Результат', 'part_of_speech' => 'noun'],
            ['word' => 'Overcome', 'translation' => 'Преодолевать', 'part_of_speech' => 'verb'],
            ['word' => 'Perceive', 'translation' => 'Воспринимать', 'part_of_speech' => 'verb'],
            ['word' => 'Persist', 'translation' => 'Настойчиво продолжать', 'part_of_speech' => 'verb'],
            ['word' => 'Plausible', 'translation' => 'Правдоподобный', 'part_of_speech' => 'adjective'],
            ['word' => 'Policy', 'translation' => 'Политика, курс', 'part_of_speech' => 'noun'],
            ['word' => 'Proportion', 'translation' => 'Пропорция', 'part_of_speech' => 'noun'],
            ['word' => 'Pursue', 'translation' => 'Преследовать, стремиться', 'part_of_speech' => 'verb'],
            ['word' => 'Rational', 'translation' => 'Разумный', 'part_of_speech' => 'adjective'],
            ['word' => 'Reinforce', 'translation' => 'Укреплять', 'part_of_speech' => 'verb'],
            ['word' => 'Reluctant', 'translation' => 'Неохотный', 'part_of_speech' => 'adjective'],
            ['word' => 'Require', 'translation' => 'Требовать', 'part_of_speech' => 'verb'],
            ['word' => 'Resolve', 'translation' => 'Решать (проблему)', 'part_of_speech' => 'verb'],
            ['word' => 'Restrict', 'translation' => 'Ограничивать', 'part_of_speech' => 'verb'],
            ['word' => 'Subsequent', 'translation' => 'Последующий', 'part_of_speech' => 'adjective'],
            ['word' => 'Sufficient', 'translation' => 'Достаточный', 'part_of_speech' => 'adjective'],
            ['word' => 'Sustain', 'translation' => 'Поддерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Transform', 'translation' => 'Преобразовывать', 'part_of_speech' => 'verb'],
        ];
    }
};
