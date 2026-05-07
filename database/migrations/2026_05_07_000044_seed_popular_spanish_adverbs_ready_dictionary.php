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
                    'name' => 'Популярные Наречия на Испанском языке',
                    'language' => 'Spanish',
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
                ->where('name', 'Популярные Наречия на Испанском языке')
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
                    'part_of_speech' => 'adverb',
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
                ->where('name', 'Популярные Наречия на Испанском языке')
                ->where('language', 'Spanish')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'Популярные Наречия на Испанском языке')
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
            ['word' => 'aquí', 'translation' => 'здесь', 'comment' => 'Моя девушка здесь - Моя девушка здесь'],
            ['word' => 'allí', 'translation' => 'там', 'comment' => 'Siempre dejo mis gafas allí - Я всегда оставляю там свои очки'],
            ['word' => 'en algún lugar', 'translation' => 'где - то', 'comment' => null],
            ['word' => 'en ninguna parte', 'translation' => 'нигде', 'comment' => 'Ya no venden esta sopa нигде - Этот суп больше нигде не продают'],
            ['word' => 'en todas partes', 'translation' => 'повсюду', 'comment' => 'El amor está en todas partes, solo necesitas saber dónde buscar - Любовь повсюду, нужно только знать, где искать'],
            ['word' => 'cerca', 'translation' => 'близко, вокруг', 'comment' => '¿Sabe si hay un banco por aquí cerca? - Вы не знаете, есть ли поблизости банк?'],
            ['word' => 'lejos', 'translation' => 'далеко', 'comment' => 'No me gusta viajar lejos de casa - Я не люблю уезжать далеко от дома'],
            ['word' => 'arriba', 'translation' => 'вверх', 'comment' => 'Мелисса наверху в своей комнате - Мелисса наверху, в своей спальне'],
            ['word' => 'abajo', 'translation' => 'вниз', 'comment' => 'Подожди внизу, пока я не закончу одеваться - Подожди внизу, пока я не закончу одеваться'],
            ['word' => 'enfrente', 'translation' => 'впереди', 'comment' => null],
            ['word' => 'detrás', 'translation' => 'позади', 'comment' => '¿Quieres saber qué hay detrás de la cortina? - Хочешь узнать, что там, за занавесом?'],
            ['word' => 'alrededor', 'translation' => 'вокруг', 'comment' => 'Me encantaría hacer un crucero alrededor del mundo - Я бы с удовольствием совершил кругосветное путешествие'],
            ['word' => 'siempre', 'translation' => 'всегда', 'comment' => 'María siempre llega tarde los lunes - Мария всегда опаздывает по понедельникам'],
            ['word' => 'nunca', 'translation' => 'никогда', 'comment' => null],
            ['word' => 'a veces', 'translation' => 'иногда', 'comment' => null],
            ['word' => 'usualmente', 'translation' => 'обычно', 'comment' => 'Обычно мы ужинаем в этом ресторане, но сегодня решили приготовить сами - Мы обычно ужинаем в этом ресторане, но сегодня решили приготовить сами'],
            ['word' => 'rara vez', 'translation' => 'редко', 'comment' => 'Rara vez escucho música, pero cuando lo hago, siempre es pop - Я редко слушаю музыку, но когда слушаю, то это всегда поп'],
            ['word' => 'a menudo', 'translation' => 'часто', 'comment' => 'Мы с Хуаном часто встречаемся, потому что живем в одном маленьком городке - Мы с Хуаном часто встречаемся, потому что живем в одном маленьком городке'],
            ['word' => 'constantemente', 'translation' => 'постоянно', 'comment' => 'No tienes que estar enojado constantemente - Необязательно злиться постоянно'],
            ['word' => 'diariamente', 'translation' => 'ежедневно', 'comment' => 'Luisa entrena diariamente, incluso si está enferma - Луиза тренируется каждый день, даже когда болеет'],
            ['word' => 'semanalmente', 'translation' => 'еженедельно', 'comment' => 'Я смотрю этот сериал раз в неделю, а ты? - Я смотрю этот сериал раз в неделю. А ты?'],
            ['word' => 'mensualmente', 'translation' => 'ежемесячно', 'comment' => 'Правда ли, что мы должны платить за подписку ежемесячно? - Правда ли, что мы должны платить за подписку ежемесячно?'],
            ['word' => 'anualmente', 'translation' => 'ежегодно', 'comment' => null],
            ['word' => 'solo / sola', 'translation' => 'один', 'comment' => 'Мне не нравится жить в одиночестве в этом доме - Мне не нравится жить одному в этом доме'],
            ['word' => 'juntos  / juntas', 'translation' => 'вместе', 'comment' => '¿Podemos ir juntos al doctor mañana? - Можем завтра вместе сходить к врачу?'],
            ['word' => 'bien', 'translation' => 'что ж', 'comment' => null],
            ['word' => 'mal', 'translation' => 'плохо, очень плохо', 'comment' => null],
            ['word' => 'más', 'translation' => 'Еще', 'comment' => 'В августе больше дождей, чем в феврале - В августе дождей больше, чем в феврале'],
            ['word' => 'menos', 'translation' => 'Меньше', 'comment' => 'До конца года осталось меньше трех часов - До конца года осталось меньше трех часов'],
            ['word' => 'poco', 'translation' => 'маленький', 'comment' => null],
            ['word' => 'poquito', 'translation' => 'немного', 'comment' => 'No me gusta bailar, pero ayer bailé un poquito en la fiesta - Я не люблю танцевать, но вчера я немного потанцевала на вечеринке'],
            ['word' => 'mucho', 'translation' => 'очень много', 'comment' => 'Ana ha estado pensando mucho sobre lo que dijiste en la reunión - Ана много думала о том, что ты сказал на собрании'],
            ['word' => 'demasiado', 'translation' => 'тоже (в значении «слишком много чего-то»)', 'comment' => 'La casa es demasiado cara y no podemos permitírnosla - Дом слишком дорогой, и мы не можем себе его позволить'],
            ['word' => 'muy', 'translation' => 'очень', 'comment' => 'Раньше я был очень ленивым, но теперь я очень активный - Раньше я был очень ленивым, но теперь я очень активный'],
            ['word' => 'mejor', 'translation' => 'лучше', 'comment' => 'Ты хорошо поработал, но, думаю, мог бы сделать это лучше - Ты хорошо поработал, но, думаю, мог бы сделать это лучше'],
            ['word' => 'peor', 'translation' => 'хуже', 'comment' => 'Наша команда играла хуже, чем когда-либо, в воскресном матче - Наша команда играла хуже, чем когда-либо, в воскресном матче'],
            ['word' => 'bastante', 'translation' => 'достаточно, вполне', 'comment' => null],
            ['word' => 'casi', 'translation' => 'почти', 'comment' => 'La caja está casi llena porque tienes muchos juguetes - Коробка почти полная, потому что у тебя много игрушек'],
            ['word' => 'aún', 'translation' => 'все еще', 'comment' => 'Aún recuerdo el primer oso de peluche que me compraste - Я до сих пор помню первого плюшевого мишку, которого ты мне купил'],
            ['word' => 'ya', 'translation' => 'уже', 'comment' => 'Ya hemos recibido la postal que mandaste desde Ipanema - Мы уже получили открытку, которую ты отправил из Ипанемы'],
            ['word' => 'hoy', 'translation' => 'Сегодня', 'comment' => 'Aunque no lo creas, hoy hemos entrenado durante tres horas - Хочешь верь, хочешь нет, но сегодня мы тренировались три часа'],
            ['word' => 'ayer', 'translation' => 'Вчера', 'comment' => 'Я болею со вчерашнего дня. Думаю, у меня грипп - Я болею со вчерашнего дня. Думаю, у меня грипп'],
            ['word' => 'mañana', 'translation' => 'завтра', 'comment' => 'Queremos ir a la playa mañana. ¡Venga con nosotros! - Мы хотим завтра пойти на пляж. Пойдем с нами!'],
            ['word' => 'ahora', 'translation' => 'сейчас', 'comment' => 'Теперь нужно просто добавить немного воды и подождать 24 часа - Теперь нужно просто добавить немного воды и подождать 24 часа'],
            ['word' => 'pronto', 'translation' => 'скоро', 'comment' => 'Tan pronto como llegue a casa, me daré un baño de agua caliente - Как только я доберусь до дома, я приму горячую ванну'],
            ['word' => 'temprano', 'translation' => 'ранний', 'comment' => 'Debo levantarme temprano mañana porque quiero repasar antes del examen - Завтра мне нужно встать пораньше, потому что я хочу повторить материал перед экзаменом'],
            ['word' => 'tarde', 'translation' => 'поздно', 'comment' => 'Se está haciendo tarde. ¿Quiere que la lleve a casa? - Уже поздно. Хочешь, я отвезу тебя домой?'],
            ['word' => 'entonces', 'translation' => 'тогда', 'comment' => 'С тех пор Мигель больше не видел своего отца - С тех пор Мигель больше не видел своего отца'],
            ['word' => 'esta noche', 'translation' => 'сегодня вечером', 'comment' => 'No me espere levantado esta noche porque llegaré tarde - Не жди меня сегодня, я опоздаю'],
            ['word' => 'anoche', 'translation' => 'прошлой ночью', 'comment' => 'Прошлой ночью в конце улицы раздался сильный шум - Прошлой ночью в конце улицы раздался громкий шум'],
            ['word' => 'sí', 'translation' => 'ДА', 'comment' => 'Человек 1: ¿Has llamado ya a mamá? - Ты уже позвонил маме?'],
            ['word' => 'también', 'translation' => 'также', 'comment' => 'Me dijeron que ellos también llegaron tarde porque no podían encontrar las llaves del coche - Они сказали мне, что тоже приехали поздно, потому что не могли найти ключи от машины'],
            ['word' => 'cierto', 'translation' => 'истинный', 'comment' => null],
            ['word' => 'obvio', 'translation' => 'очевидно, конечно', 'comment' => 'Человек 1: ¿Ya tienes licencia de conducir? - У тебя уже есть водительские права?'],
            ['word' => 'claro', 'translation' => 'конечно', 'comment' => 'Claro, puedes venir a visitarnos cuando quieras - Конечно, ты можешь прийти к нам в гости, когда захочешь'],
            ['word' => 'seguro', 'translation' => 'конечно, конечно', 'comment' => 'Seguro que no habrá segunda temporada de esa serie - Вряд ли будет второй сезон этого сериала'],
            ['word' => 'asimismo', 'translation' => 'точно так же', 'comment' => 'Asimismo, в коридорах запрещено курить - Кроме того, в коридорах запрещено курить'],
            ['word' => 'efectivamente', 'translation' => 'в самом деле', 'comment' => 'Efectivamente, в последний раз я приезжал сюда до аварии с Марселой - Действительно, в последний раз я приезжал сюда до аварии с Марселой'],
            ['word' => 'indudablemente', 'translation' => 'несомненно', 'comment' => 'Indudablemente, eres uno de los mejores estudiantes de la universidad - Несомненно, ты один из лучших студентов в университете'],
            ['word' => 'realmente', 'translation' => 'действительно, на самом деле', 'comment' => 'Человек 1: ¿Vienes por aquí seguido? - Ты часто здесь бываешь?'],
            ['word' => 'НЕТ', 'translation' => 'нет, не так', 'comment' => '¡No me puedo creer que hayas ganado la lotería otra vez! - Не могу поверить, что ты снова выиграл в лотерею!'],
            ['word' => 'ni', 'translation' => 'ни то, ни другое', 'comment' => 'Ni lo sé, ni me importa - Я не знаю и мне всё равно'],
            ['word' => 'tampoco', 'translation' => 'либо то, либо другое', 'comment' => 'Мой папа тоже не пришел потому что ему нужно было работать - Мой папа тоже не пришел, потому что ему нужно было работать'],
            ['word' => 'en absoluto', 'translation' => 'Вовсе нет, совсем нет', 'comment' => null],
            ['word' => 'nunca', 'translation' => 'никогда', 'comment' => 'Nunca querés ayudarme cuando lo necesito - Ты никогда не хочешь помочь мне, когда я в этом нуждаюсь'],
            ['word' => 'jamás', 'translation' => 'никогда, никогда', 'comment' => 'Jamás olvidaré la primera vez que vi la nieve - Я никогда не забуду, как впервые увидел снег'],
            ['word' => 'ni siquiera', 'translation' => 'даже не', 'comment' => 'Ni siquiera me dijo que no vendría - Она даже не сказала мне, что не придет'],
            ['word' => 'ничего', 'translation' => 'почти ничего, совсем ничего', 'comment' => null],
            ['word' => 'de ninguna manera', 'translation' => 'ни в коем случае, ни при каких обстоятельствах', 'comment' => 'No voy a aceptarlo de ninguna manera - Ни за что на свете я этого не приму'],
            ['word' => 'en ningún caso', 'translation' => 'ни при каких обстоятельствах', 'comment' => null],
            ['word' => 'quizá/quizás', 'translation' => 'возможно', 'comment' => 'Quizá мне придется вернуться домой, потому что я не могу найти свой телефон - Возможно, мне придется вернуться домой, потому что я не могу найти свой телефон'],
            ['word' => 'tal vez', 'translation' => 'может быть', 'comment' => 'Возможно наша любовь невозможна, но я не сдамся - Может быть, наша любовь невозможна, но я не сдамся'],
            ['word' => 'acaso', 'translation' => 'возможно, случайно', 'comment' => '¿Acaso pensás que soy un cajero automático? ¡No te daré más plata! - Ты что, думаешь, я банкомат? Я больше не дам тебе денег!'],
            ['word' => 'igual', 'translation' => 'в любом случае, даже несмотря на это, неважно', 'comment' => 'Igual tenemos que trabajar el domingo - В любом случае нам нужно работать в воскресенье'],
            ['word' => 'a lo mejor', 'translation' => 'может быть, вероятно, в лучшем случае', 'comment' => 'A lo mejor Альфонсо не знает, что мы здесь - Может быть, Альфонсо не знает, что мы здесь'],
            ['word' => 'lo mismo', 'translation' => 'может быть', 'comment' => 'Lo mismo vuelvo cansado y me voy a la cama - Может быть, я вернусь уставшим и сразу лягу спать'],
            ['word' => 'al parecer', 'translation' => 'очевидно', 'comment' => 'Al parecer, necesito un permiso de obra para renovar la cocina - Судя по всему, мне нужно разрешение на ремонт кухни'],
            ['word' => 'aparentemente', 'translation' => 'очевидно', 'comment' => 'Aparentemente, не все то золото, что блестит - Видимо, не все то золото, что блестит'],
            ['word' => 'posiblemente', 'translation' => 'возможно', 'comment' => 'Возможно я выберу тебя, потому что у тебя больше свободного времени - Возможно, я выберу тебя, потому что у тебя больше свободного времени'],
            ['word' => 'probablemente', 'translation' => 'вероятно', 'comment' => 'Вероятно ничего серьезного, но мне нужно это проверить - Скорее всего, ничего серьезного, но мне нужно это проверить'],
            ['word' => 'qué', 'translation' => 'итак', 'comment' => '¡Qué guapa estás, Maricruz! - Ты [такая] красивая, Марикрус!'],
            ['word' => 'cómo', 'translation' => 'как', 'comment' => '¿Cómo es posible que siempre tengas razón? - Как это возможно, что ты всегда прав?'],
            ['word' => 'dónde', 'translation' => 'где', 'comment' => '¿Sabés dónde dejé mis libros? No logro encontrarlos - Ты не знаешь, где я оставил свои книги? Я не могу их найти'],
            ['word' => 'a dónde', 'translation' => 'куда ехать', 'comment' => 'Он не сказал, куда хочет пойти, так что мы не можем ему помочь - Он не сказал, куда хочет пойти [в], так что мы не можем ему помочь'],
            ['word' => 'de dónde', 'translation' => 'откуда', 'comment' => '¿De dónde es tu novia? Habla muy bien español - Откуда твоя девушка? Она очень хорошо говорит по-испански'],
            ['word' => 'hasta dónde', 'translation' => 'как далеко', 'comment' => '¿Hasta dónde estás dispuesto a llegar para conseguir el premio? - Как далеко вы готовы зайти, чтобы выиграть приз?'],
            ['word' => 'cuándo', 'translation' => 'когда', 'comment' => '¿Cúando fue la última vez que hablaste con Sarita? - Когда ты в последний раз разговаривал с Саритой?'],
            ['word' => 'desde cuándo', 'translation' => 'с каких пор и как давно', 'comment' => '¿Desde cuándo vives en México? - Как давно вы живете в Мексике?'],
            ['word' => 'cuánto', 'translation' => 'как', 'comment' => '¡Cuánto me gustaría ir de vacaciones a Argentina! - Как бы я хотел поехать в отпуск в Аргентину!'],
            ['word' => 'cuán', 'translation' => 'как', 'comment' => 'No supe entender cuán ciego estuve todo ese tiempo - Я не мог понять, насколько слепым я был все это время'],
            ['word' => 'donde', 'translation' => 'где', 'comment' => 'Это кафетерий где мы с Хуаной познакомились - Это кафетерий, где мы с Хуаной познакомились'],
            ['word' => 'hacia donde', 'translation' => 'куда, к какому', 'comment' => 'Aquella es la tienda hacia donde se dirigen todos los niños - Это магазин, куда направляются все дети'],
            ['word' => 'desde donde', 'translation' => 'откуда, из какого', 'comment' => 'Это город, из которого распространился вирус - Это город, из которого распространился вирус'],
            ['word' => 'al que  / a la que', 'translation' => 'к которому', 'comment' => 'Ese es el gimnasio al que siempre voy - Это спортзал, [в который] я всегда хожу'],
            ['word' => 'en el que  / en la que', 'translation' => 'где, в каком', 'comment' => 'Пожалуйста, покажите мне университет, в котором вы изучали медицину - Пожалуйста, покажите мне университет, где вы изучали медицину'],
            ['word' => 'del que  / de la que', 'translation' => 'откуда, из какого', 'comment' => 'Esta es la ciudad de la que vengo - Это город, [из которого] я родом'],
            ['word' => 'por el que  / por la que', 'translation' => 'через который', 'comment' => '¿Me podés mostrar la ventana por la que lanzaste la pelota? - Можешь показать мне окно, через которое ты бросил мяч?'],
            ['word' => 'cuando', 'translation' => 'когда', 'comment' => 'Cuando deje de llover, iremos a la biblioteca - Когда перестанет дождь, мы пойдем в библиотеку'],
            ['word' => 'como', 'translation' => 'ну, типа того', 'comment' => 'Debemos hacerlo como nos dijo mamá - Мы должны сделать так, как сказала мама'],
            ['word' => 'cuanto', 'translation' => 'настолько, насколько, все', 'comment' => null],
        ];
    }
};