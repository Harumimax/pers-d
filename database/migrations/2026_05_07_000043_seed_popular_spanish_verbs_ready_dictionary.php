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
                    'name' => 'Популярные Глаголы на Испанском языке',
                    'language' => 'Spanish',
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
                ->where('name', 'Популярные Глаголы на Испанском языке')
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
                    'part_of_speech' => 'verb',
                    'comment' => $word['comment'],
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
                ->where('name', 'Популярные Глаголы на Испанском языке')
                ->where('language', 'Spanish')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'Популярные Глаголы на Испанском языке')
                ->where('language', 'Spanish')
                ->delete();
        });
    }

    /**
     * @return array<int, array{word: string, translation: string, comment: ?string}>
     */
    private function words(): array
    {
        return [
            ['word' => 'ser', 'translation' => 'быть', 'comment' => null],
            ['word' => 'estar', 'translation' => 'находиться', 'comment' => null],
            ['word' => 'haber', 'translation' => 'иметь (вспомогательный глагол)', 'comment' => null],
            ['word' => 'tener', 'translation' => 'иметь', 'comment' => null],
            ['word' => 'hacer', 'translation' => 'делать', 'comment' => null],
            ['word' => 'poder', 'translation' => 'мочь', 'comment' => null],
            ['word' => 'decir', 'translation' => 'сказать', 'comment' => null],
            ['word' => 'ir', 'translation' => 'идти', 'comment' => null],
            ['word' => 'ver', 'translation' => 'видеть', 'comment' => null],
            ['word' => 'dar', 'translation' => 'давать', 'comment' => null],
            ['word' => 'saber', 'translation' => 'знать', 'comment' => null],
            ['word' => 'querer', 'translation' => 'хотеть', 'comment' => null],
            ['word' => 'llegar', 'translation' => 'прибывать', 'comment' => null],
            ['word' => 'pasar', 'translation' => 'проходить', 'comment' => null],
            ['word' => 'deber', 'translation' => 'быть должным', 'comment' => null],
            ['word' => 'poner', 'translation' => 'класть', 'comment' => null],
            ['word' => 'parecer', 'translation' => 'казаться', 'comment' => null],
            ['word' => 'quedar', 'translation' => 'оставаться', 'comment' => null],
            ['word' => 'creer', 'translation' => 'верить', 'comment' => null],
            ['word' => 'hablar', 'translation' => 'говорить', 'comment' => null],
            ['word' => 'llevar', 'translation' => 'нести', 'comment' => null],
            ['word' => 'dejar', 'translation' => 'оставлять', 'comment' => null],
            ['word' => 'seguir', 'translation' => 'следовать', 'comment' => null],
            ['word' => 'encontrar', 'translation' => 'находить', 'comment' => null],
            ['word' => 'llamar', 'translation' => 'звать', 'comment' => null],
            ['word' => 'venir', 'translation' => 'приходить', 'comment' => null],
            ['word' => 'pensar', 'translation' => 'думать', 'comment' => null],
            ['word' => 'salir', 'translation' => 'выходить', 'comment' => null],
            ['word' => 'volver', 'translation' => 'возвращаться', 'comment' => null],
            ['word' => 'tomar', 'translation' => 'брать', 'comment' => null],
            ['word' => 'conocer', 'translation' => 'знать, быть знакомым', 'comment' => null],
            ['word' => 'vivir', 'translation' => 'жить', 'comment' => null],
            ['word' => 'sentir', 'translation' => 'чувствовать', 'comment' => null],
            ['word' => 'tratar', 'translation' => 'пытаться', 'comment' => null],
            ['word' => 'mirar', 'translation' => 'смотреть', 'comment' => null],
            ['word' => 'contar', 'translation' => 'считать, рассказывать', 'comment' => null],
            ['word' => 'empezar', 'translation' => 'начинать', 'comment' => null],
            ['word' => 'esperar', 'translation' => 'ждать', 'comment' => null],
            ['word' => 'buscar', 'translation' => 'искать', 'comment' => null],
            ['word' => 'entrar', 'translation' => 'входить', 'comment' => null],
            ['word' => 'trabajar', 'translation' => 'работать', 'comment' => null],
            ['word' => 'escribir', 'translation' => 'писать', 'comment' => null],
            ['word' => 'perder', 'translation' => 'терять', 'comment' => null],
            ['word' => 'producir', 'translation' => 'производить', 'comment' => null],
            ['word' => 'ocurrir', 'translation' => 'происходить', 'comment' => null],
            ['word' => 'entender', 'translation' => 'понимать', 'comment' => null],
            ['word' => 'pedir', 'translation' => 'просить', 'comment' => null],
            ['word' => 'recibir', 'translation' => 'получать', 'comment' => null],
            ['word' => 'recordar', 'translation' => 'помнить', 'comment' => null],
            ['word' => 'terminar', 'translation' => 'заканчивать', 'comment' => null],
            ['word' => 'permitir', 'translation' => 'позволять', 'comment' => null],
            ['word' => 'aparecer', 'translation' => 'появляться', 'comment' => null],
            ['word' => 'conseguir', 'translation' => 'достигать', 'comment' => null],
            ['word' => 'comenzar', 'translation' => 'начинать', 'comment' => null],
            ['word' => 'servir', 'translation' => 'служить', 'comment' => null],
            ['word' => 'sacar', 'translation' => 'вынимать', 'comment' => null],
            ['word' => 'necesitar', 'translation' => 'нуждаться', 'comment' => null],
            ['word' => 'mantener', 'translation' => 'поддерживать', 'comment' => null],
            ['word' => 'resultar', 'translation' => 'оказываться', 'comment' => null],
            ['word' => 'leer', 'translation' => 'читать', 'comment' => null],
            ['word' => 'caer', 'translation' => 'падать', 'comment' => null],
            ['word' => 'cambiar', 'translation' => 'менять', 'comment' => null],
            ['word' => 'presentar', 'translation' => 'представлять', 'comment' => null],
            ['word' => 'crear', 'translation' => 'создавать', 'comment' => null],
            ['word' => 'abrir', 'translation' => 'открывать', 'comment' => null],
            ['word' => 'considerar', 'translation' => 'рассматривать', 'comment' => null],
            ['word' => 'oír', 'translation' => 'слышать', 'comment' => null],
            ['word' => 'acabar', 'translation' => 'заканчивать', 'comment' => null],
            ['word' => 'convertir', 'translation' => 'превращать', 'comment' => null],
            ['word' => 'ganar', 'translation' => 'выигрывать', 'comment' => null],
            ['word' => 'formar', 'translation' => 'формировать', 'comment' => null],
            ['word' => 'traer', 'translation' => 'приносить', 'comment' => null],
            ['word' => 'partir', 'translation' => 'разделять', 'comment' => null],
            ['word' => 'morir', 'translation' => 'умирать', 'comment' => null],
            ['word' => 'aceptar', 'translation' => 'принимать', 'comment' => null],
            ['word' => 'realizar', 'translation' => 'реализовать', 'comment' => null],
            ['word' => 'suponer', 'translation' => 'предполагать', 'comment' => null],
            ['word' => 'comprender', 'translation' => 'понимать', 'comment' => null],
            ['word' => 'lograr', 'translation' => 'достигать', 'comment' => null],
            ['word' => 'explicar', 'translation' => 'объяснять', 'comment' => null],
            ['word' => 'preguntar', 'translation' => 'спрашивать', 'comment' => null],
            ['word' => 'tocar', 'translation' => 'трогать, играть (на инструменте)', 'comment' => null],
            ['word' => 'reconocer', 'translation' => 'признавать', 'comment' => null],
            ['word' => 'estudiar', 'translation' => 'учиться', 'comment' => null],
            ['word' => 'alcanzar', 'translation' => 'достигать', 'comment' => null],
            ['word' => 'nacer', 'translation' => 'рождаться', 'comment' => null],
            ['word' => 'dirigir', 'translation' => 'руководить', 'comment' => null],
            ['word' => 'correr', 'translation' => 'бежать', 'comment' => null],
            ['word' => 'utilizar', 'translation' => 'использовать', 'comment' => null],
            ['word' => 'pagar', 'translation' => 'платить', 'comment' => null],
            ['word' => 'ayudar', 'translation' => 'помогать', 'comment' => null],
            ['word' => 'gustar', 'translation' => 'нравиться', 'comment' => null],
            ['word' => 'jugar', 'translation' => 'играть', 'comment' => null],
            ['word' => 'escuchar', 'translation' => 'слушать', 'comment' => null],
            ['word' => 'cumplir', 'translation' => 'выполнять', 'comment' => null],
            ['word' => 'ofrecer', 'translation' => 'предлагать', 'comment' => null],
            ['word' => 'descubrir', 'translation' => 'открывать (что-то новое)', 'comment' => null],
            ['word' => 'levantar', 'translation' => 'поднимать', 'comment' => null],
            ['word' => 'intentar', 'translation' => 'пытаться', 'comment' => null],
            ['word' => 'usar', 'translation' => 'использовать', 'comment' => null],
        ];
    }
};