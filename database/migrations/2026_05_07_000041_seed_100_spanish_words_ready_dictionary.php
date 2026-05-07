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
                    'name' => '100 Spanish words',
                    'language' => 'Spanish',
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
                ->where('name', '100 Spanish words')
                ->where('language', 'Spanish')
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
                ->where('name', '100 Spanish words')
                ->where('language', 'Spanish')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', '100 Spanish words')
                ->where('language', 'Spanish')
                ->delete();
        });
    }

    /**
     * @return array<int, array{word: string, translation: string, part_of_speech: string}>
     */
    private function words(): array
    {
        return [
            ['word' => 'Abandonar', 'translation' => 'Покидать, оставлять', 'part_of_speech' => 'verb'],
            ['word' => 'Abundante', 'translation' => 'Обильный, избыточный', 'part_of_speech' => 'adjective'],
            ['word' => 'Lograr', 'translation' => 'Выполнять, достигать', 'part_of_speech' => 'verb'],
            ['word' => 'Preciso', 'translation' => 'Точный', 'part_of_speech' => 'adjective'],
            ['word' => 'Reconocer', 'translation' => 'Признавать', 'part_of_speech' => 'verb'],
            ['word' => 'Adaptarse', 'translation' => 'Приспосабливаться', 'part_of_speech' => 'verb'],
            ['word' => 'Adecuado', 'translation' => 'Достаточный, соответствующий', 'part_of_speech' => 'adjective'],
            ['word' => 'Defender', 'translation' => 'Защищать, поддерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Afectar', 'translation' => 'Влиять', 'part_of_speech' => 'verb'],
            ['word' => 'Ambiguo', 'translation' => 'Двусмысленный, неоднозначный', 'part_of_speech' => 'adjective'],
            ['word' => 'Anticipar', 'translation' => 'Ожидать, предвидеть', 'part_of_speech' => 'verb'],
            ['word' => 'Aparente', 'translation' => 'Очевидный, явный', 'part_of_speech' => 'adjective'],
            ['word' => 'Atraer', 'translation' => 'Привлекать', 'part_of_speech' => 'verb'],
            ['word' => 'Apropiado', 'translation' => 'Подходящий', 'part_of_speech' => 'adjective'],
            ['word' => 'Arbitrario', 'translation' => 'Произвольный', 'part_of_speech' => 'adjective'],
            ['word' => 'Afirmar', 'translation' => 'Утверждать', 'part_of_speech' => 'verb'],
            ['word' => 'Evaluar', 'translation' => 'Оценивать', 'part_of_speech' => 'verb'],
            ['word' => 'Asignar', 'translation' => 'Назначать, присваивать', 'part_of_speech' => 'verb'],
            ['word' => 'Suponer', 'translation' => 'Предполагать', 'part_of_speech' => 'verb'],
            ['word' => 'Asegurar', 'translation' => 'Уверять', 'part_of_speech' => 'verb'],
            ['word' => 'Adjuntar', 'translation' => 'Прикреплять', 'part_of_speech' => 'verb'],
            ['word' => 'Alcanzar', 'translation' => 'Достигать', 'part_of_speech' => 'verb'],
            ['word' => 'Consciente', 'translation' => 'Осведомленный', 'part_of_speech' => 'adjective'],
            ['word' => 'Benevolente', 'translation' => 'Доброжелательный', 'part_of_speech' => 'adjective'],
            ['word' => 'Sesgo', 'translation' => 'Предвзятость', 'part_of_speech' => 'noun'],
            ['word' => 'Extraño', 'translation' => 'Странный', 'part_of_speech' => 'adjective'],
            ['word' => 'Aclarar', 'translation' => 'Уточнять, прояснять', 'part_of_speech' => 'verb'],
            ['word' => 'Coherente', 'translation' => 'Последовательный, связный', 'part_of_speech' => 'adjective'],
            ['word' => 'Compromiso', 'translation' => 'Обязательство', 'part_of_speech' => 'noun'],
            ['word' => 'Obligar', 'translation' => 'Заставлять, вынуждать', 'part_of_speech' => 'verb'],
            ['word' => 'Integral', 'translation' => 'Всеобъемлющий', 'part_of_speech' => 'adjective'],
            ['word' => 'Concebir', 'translation' => 'Задумать, представить', 'part_of_speech' => 'verb'],
            ['word' => 'Concluir', 'translation' => 'Заключать, делать вывод', 'part_of_speech' => 'verb'],
            ['word' => 'Condenar', 'translation' => 'Осуждать', 'part_of_speech' => 'verb'],
            ['word' => 'Limitar', 'translation' => 'Ограничивать', 'part_of_speech' => 'verb'],
            ['word' => 'Consentimiento', 'translation' => 'Согласие', 'part_of_speech' => 'noun'],
            ['word' => 'Consistente', 'translation' => 'Последовательный', 'part_of_speech' => 'adjective'],
            ['word' => 'Restringir', 'translation' => 'Ограничивать, сдерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Contemporáneo', 'translation' => 'Современный', 'part_of_speech' => 'adjective'],
            ['word' => 'Contradecir', 'translation' => 'Противоречить', 'part_of_speech' => 'verb'],
            ['word' => 'Transmitir', 'translation' => 'Передавать', 'part_of_speech' => 'verb'],
            ['word' => 'Convencer', 'translation' => 'Убеждать', 'part_of_speech' => 'verb'],
            ['word' => 'Fundamental', 'translation' => 'Основной', 'part_of_speech' => 'adjective'],
            ['word' => 'Corresponder', 'translation' => 'Соответствовать', 'part_of_speech' => 'verb'],
            ['word' => 'Crucial', 'translation' => 'Ключевой, решающий', 'part_of_speech' => 'adjective'],
            ['word' => 'Negar', 'translation' => 'Отрицать', 'part_of_speech' => 'verb'],
            ['word' => 'Derivar', 'translation' => 'Получать, извлекать', 'part_of_speech' => 'verb'],
            ['word' => 'Dedicar', 'translation' => 'Посвящать', 'part_of_speech' => 'verb'],
            ['word' => 'Disminuir', 'translation' => 'Уменьшать', 'part_of_speech' => 'verb'],
            ['word' => 'Distinto', 'translation' => 'Отчетливый, различимый', 'part_of_speech' => 'adjective'],
            ['word' => 'Diverso', 'translation' => 'Разнообразный', 'part_of_speech' => 'adjective'],
            ['word' => 'Surgir', 'translation' => 'Появляться', 'part_of_speech' => 'verb'],
            ['word' => 'Mejorar', 'translation' => 'Улучшать, усиливать', 'part_of_speech' => 'verb'],
            ['word' => 'Garantizar', 'translation' => 'Обеспечивать, гарантировать', 'part_of_speech' => 'verb'],
            ['word' => 'Entidad', 'translation' => 'Сущность, объект', 'part_of_speech' => 'noun'],
            ['word' => 'Exagerar', 'translation' => 'Преувеличивать', 'part_of_speech' => 'verb'],
            ['word' => 'Explícito', 'translation' => 'Явный, откровенный', 'part_of_speech' => 'adjective'],
            ['word' => 'Marco', 'translation' => 'Структура, рамки', 'part_of_speech' => 'noun'],
            ['word' => 'Generar', 'translation' => 'Производить, генерировать', 'part_of_speech' => 'verb'],
            ['word' => 'Otorgar', 'translation' => 'Предоставлять', 'part_of_speech' => 'verb'],
            ['word' => 'Por lo tanto', 'translation' => 'Следовательно', 'part_of_speech' => 'adverb'],
            ['word' => 'Hipótesis', 'translation' => 'Гипотеза', 'part_of_speech' => 'noun'],
            ['word' => 'Implicar', 'translation' => 'Подразумевать', 'part_of_speech' => 'verb'],
            ['word' => 'Incentivo', 'translation' => 'Стимул', 'part_of_speech' => 'noun'],
            ['word' => 'Incorporar', 'translation' => 'Включать в себя', 'part_of_speech' => 'verb'],
            ['word' => 'Indicar', 'translation' => 'Указывать', 'part_of_speech' => 'verb'],
            ['word' => 'Inevitable', 'translation' => 'Неизбежный', 'part_of_speech' => 'adjective'],
            ['word' => 'Inferir', 'translation' => 'Делать вывод', 'part_of_speech' => 'verb'],
            ['word' => 'Inhibir', 'translation' => 'Сдерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Iniciar', 'translation' => 'Начинать', 'part_of_speech' => 'verb'],
            ['word' => 'Perspicacia', 'translation' => 'Проницательность', 'part_of_speech' => 'noun'],
            ['word' => 'Integral', 'translation' => 'Неотъемлемый', 'part_of_speech' => 'adjective'],
            ['word' => 'Intenso', 'translation' => 'Интенсивный', 'part_of_speech' => 'adjective'],
            ['word' => 'Intervenir', 'translation' => 'Вмешиваться', 'part_of_speech' => 'verb'],
            ['word' => 'Justificar', 'translation' => 'Оправдывать', 'part_of_speech' => 'verb'],
            ['word' => 'Legítimo', 'translation' => 'Законный', 'part_of_speech' => 'adjective'],
            ['word' => 'Manipular', 'translation' => 'Манипулировать', 'part_of_speech' => 'verb'],
            ['word' => 'Maduro', 'translation' => 'Зрелый', 'part_of_speech' => 'adjective'],
            ['word' => 'Descuidar', 'translation' => 'Пренебрегать', 'part_of_speech' => 'verb'],
            ['word' => 'Noción', 'translation' => 'Понятие, представление', 'part_of_speech' => 'noun'],
            ['word' => 'Obtener', 'translation' => 'Получать', 'part_of_speech' => 'verb'],
            ['word' => 'Obvio', 'translation' => 'Очевидный', 'part_of_speech' => 'adjective'],
            ['word' => 'Resultado', 'translation' => 'Результат', 'part_of_speech' => 'noun'],
            ['word' => 'Superar', 'translation' => 'Преодолевать', 'part_of_speech' => 'verb'],
            ['word' => 'Percibir', 'translation' => 'Воспринимать', 'part_of_speech' => 'verb'],
            ['word' => 'Persistir', 'translation' => 'Настойчиво продолжать', 'part_of_speech' => 'verb'],
            ['word' => 'Plausible', 'translation' => 'Правдоподобный', 'part_of_speech' => 'adjective'],
            ['word' => 'Política', 'translation' => 'Политика, курс', 'part_of_speech' => 'noun'],
            ['word' => 'Proporción', 'translation' => 'Пропорция', 'part_of_speech' => 'noun'],
            ['word' => 'Perseguir', 'translation' => 'Преследовать, стремиться', 'part_of_speech' => 'verb'],
            ['word' => 'Racional', 'translation' => 'Разумный', 'part_of_speech' => 'adjective'],
            ['word' => 'Reforzar', 'translation' => 'Укреплять', 'part_of_speech' => 'verb'],
            ['word' => 'Reacio', 'translation' => 'Неохотный', 'part_of_speech' => 'adjective'],
            ['word' => 'Requerir', 'translation' => 'Требовать', 'part_of_speech' => 'verb'],
            ['word' => 'Resolver', 'translation' => 'Решать проблему', 'part_of_speech' => 'verb'],
            ['word' => 'Restringir', 'translation' => 'Ограничивать', 'part_of_speech' => 'verb'],
            ['word' => 'Posterior', 'translation' => 'Последующий', 'part_of_speech' => 'adjective'],
            ['word' => 'Suficiente', 'translation' => 'Достаточный', 'part_of_speech' => 'adjective'],
            ['word' => 'Mantener', 'translation' => 'Поддерживать', 'part_of_speech' => 'verb'],
            ['word' => 'Transformar', 'translation' => 'Преобразовывать', 'part_of_speech' => 'verb'],
        ];
    }
};
