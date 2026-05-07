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
                    'name' => 'Популярные Прилагательные на Испанском языке',
                    'language' => 'Spanish',
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
                ->where('name', 'Популярные Прилагательные на Испанском языке')
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
                    'part_of_speech' => 'adjective',
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
                ->where('name', 'Популярные Прилагательные на Испанском языке')
                ->where('language', 'Spanish')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'Популярные Прилагательные на Испанском языке')
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
            ['word' => 'aburrido', 'translation' => 'скучный', 'comment' => 'un día aburrido - скучный день'],
            ['word' => 'agradable', 'translation' => 'приятный, милый', 'comment' => 'una reunión agradable - приятная встреча'],
            ['word' => 'alegre', 'translation' => 'весёлый', 'comment' => 'una vida alegre - весёлая жизнь'],
            ['word' => 'alto', 'translation' => 'высокий', 'comment' => 'un hombre alto - высокий мужчина'],
            ['word' => 'amargo', 'translation' => 'горький', 'comment' => 'un sabor amargo - горький вкус'],
            ['word' => 'ancho', 'translation' => 'широкий', 'comment' => 'un cinturón ancho - широкий пояс'],
            ['word' => 'antiguo', 'translation' => 'старый, старинный', 'comment' => 'un armario antiguo - старинный шкаф'],
            ['word' => 'azul', 'translation' => 'синий', 'comment' => 'un cielo azul - синее небо'],
            ['word' => 'barato', 'translation' => 'дешёвый', 'comment' => 'una camisa barata - дешёвая рубашка'],
            ['word' => 'bajo', 'translation' => 'низкий', 'comment' => 'una temperatura baja - низкая температура'],
            ['word' => 'bello', 'translation' => 'красивый, чудесный', 'comment' => 'un escenario bello - чудесный сценарий'],
            ['word' => 'blanco', 'translation' => 'белый', 'comment' => 'una pared blanca - белая стена'],
            ['word' => 'blando', 'translation' => 'слабый, мягкий', 'comment' => 'un poder blando - мягкая сила'],
            ['word' => 'bonito', 'translation' => 'милый, красивый', 'comment' => 'un gesto bonito - красивый жест'],
            ['word' => 'bueno', 'translation' => 'хороший', 'comment' => 'una madre buena - хорошая мать'],
            ['word' => 'caliente', 'translation' => 'горячий', 'comment' => 'un café caliente - горячий кофе'],
            ['word' => 'capaz', 'translation' => 'способный, умелый', 'comment' => 'un político capaz - умелый политик'],
            ['word' => 'caro', 'translation' => 'дорогой', 'comment' => 'un anillo caro - дорогое кольцо'],
            ['word' => 'casado', 'translation' => 'женатый', 'comment' => 'una mujer casada - замужняя женщина'],
            ['word' => 'colorido', 'translation' => 'яркий, красочный, цветной', 'comment' => 'un dibujo colorido - красочный рисунок'],
            ['word' => 'cómodo', 'translation' => 'удобный', 'comment' => 'un sofá cómodo - удобный диван'],
            ['word' => 'conocido', 'translation' => 'знакомый, известный', 'comment' => 'una persona conocida - знакомый человек'],
            ['word' => 'contento', 'translation' => 'довольный, радостный', 'comment' => 'un perro contento - довольный пёс'],
            ['word' => 'corto', 'translation' => 'короткий', 'comment' => 'una memoria corta - короткая память'],
            ['word' => 'cuadrado', 'translation' => 'квадратный', 'comment' => 'una tuerca cuadrada - квадратная гайка'],
            ['word' => 'débil', 'translation' => 'слабый', 'comment' => 'un carácter débil - слабый характер'],
            ['word' => 'delgado', 'translation' => 'худой, стройный', 'comment' => 'un tío delgado - худой парень'],
            ['word' => 'desagradable', 'translation' => 'неприятный', 'comment' => 'una conversación desagradable - неприятный разговор'],
            ['word' => 'descolorido', 'translation' => 'бесцветный', 'comment' => 'un cristal descolorido - бесцветный кристалл'],
            ['word' => 'desconocido', 'translation' => 'незнакомый, неизвестный', 'comment' => 'un objeto desconocido - неизвестный предмет'],
            ['word' => 'descontento', 'translation' => 'недовольный', 'comment' => 'una cara descontenta - недовольное лицо'],
            ['word' => 'diferente', 'translation' => 'другой, разный', 'comment' => 'un país diferente - другая страна'],
            ['word' => 'difícil', 'translation' => 'сложный', 'comment' => 'un nivel difícil - сложный уровень'],
            ['word' => 'divertido', 'translation' => 'забавный, весёлый', 'comment' => 'una historia divertida - забавная история'],
            ['word' => 'dulce', 'translation' => 'сладкий', 'comment' => 'una manzana dulce - сладкое яблоко'],
            ['word' => 'duro', 'translation' => 'тяжёлый, суровый', 'comment' => 'un año duro - тяжёлый год'],
            ['word' => 'educado', 'translation' => 'воспитанный', 'comment' => 'una chica educada - воспитанная девочка'],
            ['word' => 'enfadado', 'translation' => 'злой, разгневанный', 'comment' => 'un maestro enfadado - разгневанный учитель'],
            ['word' => 'enfermo', 'translation' => 'больной', 'comment' => 'un niño enfermo - больной ребёнок'],
            ['word' => 'egoísta', 'translation' => 'эгоистичный', 'comment' => 'un acto egoísta - эгоистичный поступок'],
            ['word' => 'estrecho', 'translation' => 'узкий', 'comment' => 'un pasaje estrecho - узкий проход'],
            ['word' => 'fácil', 'translation' => 'лёгкий', 'comment' => 'una asignatura fácil - лёгкий предмет'],
            ['word' => 'falso', 'translation' => 'ложный', 'comment' => 'un pensamiento falso - ложная мысль'],
            ['word' => 'famoso', 'translation' => 'знаменитый', 'comment' => 'una actriz famosa - знаменитая актриса'],
            ['word' => 'feliz', 'translation' => 'счастливый', 'comment' => 'un matrimonio feliz - счастливый брак'],
            ['word' => 'feo', 'translation' => 'уродливый', 'comment' => 'un suéter feo - уродливый свитер'],
            ['word' => 'fresco', 'translation' => 'свежий', 'comment' => 'carne fresca - свежее мясо'],
            ['word' => 'frío', 'translation' => 'холодный', 'comment' => 'una ducha fría - холодный душ'],
            ['word' => 'fuerte', 'translation' => 'сильный', 'comment' => 'una mujer fuerte - сильная женщина'],
            ['word' => 'gordo', 'translation' => 'толстый', 'comment' => 'un gato gordo - толстый кот'],
            ['word' => 'grande', 'translation' => 'большой', 'comment' => 'una casa grande - большой дом'],
            ['word' => 'guapo', 'translation' => 'красивый', 'comment' => 'una chica guapa - красивая девушка'],
            ['word' => 'hermoso', 'translation' => 'прекрасный', 'comment' => 'una flor hermosa - прекрасный цветок'],
            ['word' => 'húmedo', 'translation' => 'влажный, мокрый', 'comment' => 'aire húmedo - влажный воздух'],
            ['word' => 'imposible', 'translation' => 'невозможный', 'comment' => 'una final imposible - невозможный финал'],
            ['word' => 'incómodo', 'translation' => 'неудобный', 'comment' => 'una blusa incómoda - неудобная блузка'],
            ['word' => 'interesante', 'translation' => 'интересный', 'comment' => 'una idea interesante - интересная идея'],
            ['word' => 'inútil', 'translation' => 'бесполезный, ненужный', 'comment' => 'un artículo inútil - бесполезная статья'],
            ['word' => 'jóven', 'translation' => 'молодой', 'comment' => 'un caballo joven - молодой конь'],
            ['word' => 'largo', 'translation' => 'длинный', 'comment' => 'una lista larga - длинный список'],
            ['word' => 'lento', 'translation' => 'медленный', 'comment' => 'un coche lento - медленная машина'],
            ['word' => 'limpio', 'translation' => 'чистый', 'comment' => 'un estanque limpio - чистый пруд'],
            ['word' => 'listo', 'translation' => 'умный', 'comment' => 'un chico listo - умный мальчик'],
            ['word' => 'lleno', 'translation' => 'полный', 'comment' => 'un tanque lleno - полный бак'],
            ['word' => 'maleducado', 'translation' => 'невоспитанный', 'comment' => 'un hombre maleducado - невоспитанный мужчина'],
            ['word' => 'malo', 'translation' => 'плохой', 'comment' => 'un día malo - плохой день'],
            ['word' => 'el/la mejor', 'translation' => 'лучший', 'comment' => 'la mejor parte - лучшая часть'],
            ['word' => 'moderno', 'translation' => 'современный', 'comment' => 'una ciudad moderna - современный город'],
            ['word' => 'mojado', 'translation' => 'мокрый', 'comment' => 'un pañal mojado - мокрый подгузник'],
            ['word' => 'muerto', 'translation' => 'мёртвый', 'comment' => 'un poeta muerto - мёртвый поэт'],
            ['word' => 'negro', 'translation' => 'чёрный', 'comment' => 'una falda negra - чёрная юбка'],
            ['word' => 'nervioso', 'translation' => 'нервный', 'comment' => 'un estudiante nervioso - нервный студент'],
            ['word' => 'nuevo', 'translation' => 'новый', 'comment' => 'una silla nueva - новый стул'],
            ['word' => 'el/la peor', 'translation' => 'худший', 'comment' => 'la peor pesadilla - худший кошмар'],
            ['word' => 'pobre', 'translation' => 'бедный', 'comment' => 'un país pobre - бедная страна'],
            ['word' => 'popular', 'translation' => 'популярный', 'comment' => 'un cantante popular - популярный певец'],
            ['word' => 'pequeño', 'translation' => 'маленький', 'comment' => 'una deuda pequeña - маленькая задолженность'],
            ['word' => 'perfecto', 'translation' => 'идеальный', 'comment' => 'un cuerpo perfecto - идеальное тело'],
            ['word' => 'picante', 'translation' => 'острый, пряный', 'comment' => 'un pimiento picante - острый перец'],
            ['word' => 'principal', 'translation' => 'главный, основной', 'comment' => 'una idea principal - главная мысль'],
            ['word' => 'rápido', 'translation' => 'быстрый', 'comment' => 'un crecimiento rápido - быстрый рост'],
            ['word' => 'raro', 'translation' => 'странный, редкий', 'comment' => 'una pregunta rara - странный вопрос'],
            ['word' => 'redondo', 'translation' => 'круглый', 'comment' => 'una mesa redonda - круглый стол'],
            ['word' => 'rico', 'translation' => 'вкусный, богатый', 'comment' => 'una comida rica - вкусная еда'],
            ['word' => 'rojo', 'translation' => 'красный', 'comment' => 'una luz roja - красный свет'],
            ['word' => 'sano', 'translation' => 'здоровый', 'comment' => 'un peso sano - здоровый вес'],
            ['word' => 'seco', 'translation' => 'сухой', 'comment' => 'vino seco - сухое вино'],
            ['word' => 'soltero', 'translation' => 'холостой', 'comment' => 'una amiga soltera - свободная подруга'],
            ['word' => 'soso', 'translation' => 'невкусный, пресный', 'comment' => 'un postre soso - невкусный десерт'],
            ['word' => 'sucio', 'translation' => 'грязный', 'comment' => 'agua sucia - грязная вода'],
            ['word' => 'tímido', 'translation' => 'застенчивый, робкий', 'comment' => 'un chico tímido - застенчивый парень'],
            ['word' => 'tonto', 'translation' => 'глупый', 'comment' => 'una persona tonta - глупый человек'],
            ['word' => 'tranquilo', 'translation' => 'спокойный', 'comment' => 'un discurso tranquilo - спокойная речь'],
            ['word' => 'triste', 'translation' => 'грустный', 'comment' => 'una historia triste - грустная история'],
            ['word' => 'útil', 'translation' => 'полезный, удобный', 'comment' => 'un libro útil - полезная книга'],
            ['word' => 'vacío', 'translation' => 'пустой', 'comment' => 'una caja vacía - пустая коробка'],
            ['word' => 'verdadero', 'translation' => 'истинный, верный', 'comment' => 'una respuesta verdadera - истинный ответ'],
            ['word' => 'verde', 'translation' => 'зелёный', 'comment' => 'un pepino verde - зелёный огурец'],
            ['word' => 'viejo', 'translation' => 'старый', 'comment' => 'un granero viejo - старый сарай'],
            ['word' => 'vivo', 'translation' => 'живой', 'comment' => 'un ser vivo - живое существо'],
        ];
    }
};