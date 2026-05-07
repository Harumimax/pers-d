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
                    'name' => 'Идеомы английского языка',
                    'language' => 'English',
                ],
                [
                    'level' => null,
                    'part_of_speech' => null,
                    'comment' => 'Словарь устойчивых выражений и идиом английского языка.',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', 'Идеомы английского языка')
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
                    'part_of_speech' => null,
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
                ->where('name', 'Идеомы английского языка')
                ->where('language', 'English')
                ->value('id');

            if ($dictionaryId !== null) {
                DB::table('ready_dictionary_words')
                    ->where('ready_dictionary_id', $dictionaryId)
                    ->delete();
            }

            DB::table('ready_dictionaries')
                ->where('name', 'Идеомы английского языка')
                ->where('language', 'English')
                ->delete();
        });
    }

    /**
     * @return array<int, array{word: string, translation: string, comment: ?string}>
     */
    private function words(): array
    {
        return [
            ['word' => 'Break the ice', 'translation' => 'разрядить обстановку', 'comment' => 'Let me tell a joke to break the ice. - Дай расскажу шутку, чтобы разрядить обстановку.'],
            ['word' => 'Under the weather', 'translation' => 'нездоровится', 'comment' => 'I’m feeling a bit under the weather today. - Что-то я сегодня неважно себя чувствую.'],
            ['word' => 'Once in a blue moon', 'translation' => 'раз в сто лет', 'comment' => 'She visits her parents once in a blue moon. - Она навещает родителей раз в сто лет.'],
            ['word' => 'A piece of cake', 'translation' => 'проще простого', 'comment' => 'That exam was a piece of cake. - Экзамен был легкотня.'],
            ['word' => 'Costs an arm and a leg', 'translation' => 'стоит целое состояние', 'comment' => 'That car costs an arm and a leg! - Эта машина стоит как крыло от самолёта.'],
            ['word' => 'Let the cat out of the bag', 'translation' => 'проболтаться', 'comment' => 'He let the cat out of the bag about the surprise party. - Он проболтался насчёт вечеринки-сюрприза.'],
            ['word' => 'Hit the books', 'translation' => 'засесть за учёбу', 'comment' => 'I have to hit the books – finals are coming. - Нужно сесть за учебники, скоро экзамены.'],
            ['word' => 'Speak of the devil', 'translation' => 'лёгок на помине', 'comment' => 'Speak of the devil – here comes Tom! - Лёгок на помине – вот и Том!'],
            ['word' => 'Kill two birds with one stone', 'translation' => 'убить двух зайцев', 'comment' => 'By walking to the store, I killed two birds with one stone. - Прогулка до магазина убила двух зайцев: и разминка, и продукты.'],
            ['word' => 'The ball is in your court', 'translation' => 'теперь всё зависит от тебя', 'comment' => 'I’ve made my offer. Now the ball is in your court. - Я сделал предложение, теперь всё в твоих руках.'],
            ['word' => 'Bite the bullet', 'translation' => 'стиснуть зубы, решиться', 'comment' => 'I hate going to the dentist, but I’ll have to bite the bullet. - Ненавижу стоматологов, но придётся потерпеть.'],
            ['word' => 'Call it a day', 'translation' => 'на сегодня хватит', 'comment' => 'Let’s call it a day and go home. - На сегодня хватит, пошли домой.'],
            ['word' => 'Hit the sack', 'translation' => 'завалиться спать', 'comment' => 'I’m exhausted. I’ll hit the sack early. - Я вымотан. Пойду спать пораньше.'],
            ['word' => 'Cut corners', 'translation' => 'халтурить', 'comment' => 'Don’t cut corners on this project. - Не халтурь с этим проектом.'],
            ['word' => 'Pull someone’s leg', 'translation' => 'подшучивать', 'comment' => 'Relax, I’m just pulling your leg! - Да расслабься, я просто прикалываюсь.'],
            ['word' => 'Add fuel to the fire', 'translation' => 'подливать масла в огонь', 'comment' => 'His comment only added fuel to the fire. - Его комментарий только подлил масла в огонь.'],
            ['word' => 'In hot water', 'translation' => 'влипнуть', 'comment' => 'He’s in hot water after missing the deadline. - Он влип – не сдал проект в срок.'],
            ['word' => 'Miss the boat', 'translation' => 'упустить шанс', 'comment' => 'I wanted those shoes, but I missed the boat. - Хотела те туфли, но упустила момент.'],
            ['word' => 'Hit the nail on the head', 'translation' => 'попасть в точку', 'comment' => 'You hit the nail on the head with that idea. - Ты прямо в точку попал с этой идеей.'],
            ['word' => 'Burn the midnight oil', 'translation' => 'засиживаться допоздна', 'comment' => 'She’s burning the midnight oil to finish the report. - Она работает допоздна, чтобы закончить отчёт.'],
            ['word' => 'It’s not rocket science', 'translation' => 'это не высшая математика', 'comment' => 'Come on, it’s not rocket science! - Да ладно, это же не квантовая физика!'],
            ['word' => 'Let someone off the hook', 'translation' => 'отпустить с миром', 'comment' => 'I’ll let you off the hook this time. - В этот раз прощаю.'],
            ['word' => 'On thin ice', 'translation' => 'ходить по тонкому льду', 'comment' => 'You’re on thin ice with your boss. - Ты на грани – начальник уже злится.'],
            ['word' => 'Back to the drawing board', 'translation' => 'начать заново', 'comment' => 'This plan didn’t work. Back to the drawing board! - План провалился. Начинаем с нуля.'],
            ['word' => 'The tip of the iceberg', 'translation' => 'верхушка айсберга', 'comment' => 'These problems are just the tip of the iceberg. - Это только верхушка проблем.'],
            ['word' => 'Spill the beans', 'translation' => 'проболтаться', 'comment' => 'Who spilled the beans about the surprise? - Кто проболтался про сюрприз?'],
            ['word' => 'Take it with a grain of salt', 'translation' => 'воспринимать скептически', 'comment' => 'Take what he says with a grain of salt. - Не всему, что он говорит, стоит верить.'],
            ['word' => 'Barking up the wrong tree', 'translation' => 'обвинять не того', 'comment' => 'If you think I broke it, you’re barking up the wrong tree. - Это не я! Ты не того винить собрался.'],
            ['word' => 'Beat around the bush', 'translation' => 'ходить вокруг да около', 'comment' => 'Stop beating around the bush and tell me the truth. - Хватит юлить, говори уже прямо.'],
            ['word' => 'Jump on the bandwagon', 'translation' => 'поддаться моде', 'comment' => 'Now everyone’s jumping on the bandwagon and starting a podcast. - Теперь все поддались моде – подкасты записывают.'],
            ['word' => 'Be caught between a rock and a hard place', 'translation' => 'находиться в трудном положении', 'comment' => 'I feel like I\'m caught between a rock and a hard place. If I refuse to work, my boss will get angry. But if I do not go to the movies with my girlfriend, she will get angry as well.'],
            ['word' => 'Bite off more than you can chew', 'translation' => 'переоценить свои силы, взяться за непосильное дело', 'comment' => 'By accepting two part-time jobs, he is clearly biting off more than he can chew.'],
            ['word' => 'Blow off steam', 'translation' => 'дать выход чувствам', 'comment' => 'I went on a run to blow off steam after our fight.'],
            ['word' => 'Break a leg', 'translation' => 'ни пуха ни пера', 'comment' => 'Good luck with the job interview. Break a leg! And, if they don\'t like you, break their leg.'],
            ['word' => 'By the skin of your teeth', 'translation' => 'еле-еле, чудом', 'comment' => 'I submitted the assignment by the skin of my teeth. If I had done it a little bit later, the professor wouldn\'t have accepted it, and I would have had to drop the course.'],
            ['word' => 'Rain or shine', 'translation' => 'при любой погоде, что бы ни случилось', 'comment' => 'We are having a barbecue tomorrow, rain or shine.'],
            ['word' => 'Cut somebody some slack', 'translation' => 'дать поблажку, не судить слишком строго', 'comment' => 'When you\'re new at a job, colleagues and bosses cut you a little slack. They forgive minor mistakes because you\'re new.'],
            ['word' => 'Cut to the chase', 'translation' => 'перейти к самому важному', 'comment' => 'Hi everyone, we all know why we are here today, so let\'s cut to the chase.'],
            ['word' => 'Get one’s head around something', 'translation' => 'понять что-то', 'comment' => 'Wait, you two are dating now? It\'s going to take a little while for me to get my head around that!'],
            ['word' => 'Get back into the swing of things', 'translation' => 'вернуться в колею', 'comment' => 'Now that lockdown is over, it\'s time to get back into the swing of things. So I\'ll start recording videos and working on some projects I stopped a few months back.'],
            ['word' => 'Get the hang of something', 'translation' => 'освоить что-либо, приобрести сноровку', 'comment' => 'Cut him some slack. He is a newbie here. He\'ll surely get the hang of it next time.'],
            ['word' => 'Get out of hand', 'translation' => 'выйти из-под контроля', 'comment' => 'If your party gets out of hand, the neighbors will call the police.'],
            ['word' => 'Go cold turkey', 'translation' => 'резко бросить, завязать раз и навсегда', 'comment' => 'I often find the best way to quit smoking is not to cut down on the number of fags you smoke but to simply go cold turkey. Just stop at once.'],
            ['word' => 'Hang in there', 'translation' => 'держаться, не сдаваться', 'comment' => 'Work can get tough in the middle of a term but hang in there and it\'ll be OK.'],
            ['word' => 'On a shoestring', 'translation' => 'на скудные средства', 'comment' => 'We were living on a shoestring for a while after we moved to the USA, but, luckily, I got a promotion, and our situation has improved a bit.'],
            ['word' => 'Pull yourself together', 'translation' => 'взять себя в руки', 'comment' => 'I know you\'re stressed out, but you need to pull yourself together and get this report done!'],
            ['word' => 'Rule of thumb', 'translation' => 'общее правило, неписаное правило', 'comment' => 'As a rule of thumb, I do not start a new project on Fridays.'],
            ['word' => 'See eye to eye', 'translation' => 'сходиться во взглядах', 'comment' => 'I think I\'m going to quit this job. My boss doesn\'t see eye to eye with me about the arrangements.'],
            ['word' => 'Slog one’s guts out', 'translation' => 'очень упорно работать', 'comment' => 'There\'s nothing more disheartening than to slog your guts out on an assignment, only for the computer to crash and delete all your work.'],
            ['word' => 'Steal someone’s thunder', 'translation' => 'присвоить себе чужую славу или идею', 'comment' => 'My brother is the star athlete of our high school, so no matter what I succeed in, he\'s constantly stealing my thunder.'],
            ['word' => 'Take a rain check', 'translation' => 'перенести на другой раз', 'comment' => 'I\'m sorry, I\'m just too exhausted to go out tonight. Could I take a rain check?'],
            ['word' => 'Think outside the box', 'translation' => 'мыслить нестандартно', 'comment' => 'Scheduling walking meetings rather than sitting around a table is a good example of how corporate America started thinking outside the box about group discussions.'],
            ['word' => 'Have a chip on one’s shoulder', 'translation' => 'носить груз обид, быть задетым', 'comment' => 'He has had a chip on his shoulder ever since he didn\'t get the promotion he was expecting.'],
            ['word' => 'You bet', 'translation' => 'конечно', 'comment' => 'Are you coming to the party?'],
            ['word' => 'A penny for your thoughts', 'translation' => 'о чём задумался?', 'comment' => 'You look serious – a penny for your thoughts? - Ты выглядишь серьезным, о чем задумался.'],
            ['word' => 'Go the extra mile', 'translation' => 'сделать больше, чем требуется', 'comment' => 'He always goes the extra mile for clients - Он всегда делает больше, чем требуется для клиентов.'],
            ['word' => 'Face the music', 'translation' => 'отвечать за последствия', 'comment' => 'It’s time to face the music - Пришло время отвечать за последствия.'],
            ['word' => 'Get cold feet', 'translation' => 'струсить, передумать из-за страха', 'comment' => 'He got cold feet before the interview - Он струсил перед собеседованием.'],
            ['word' => 'On cloud nine', 'translation' => 'быть на седьмом небе', 'comment' => 'She was on cloud nine after the news - Она была на седьмом небе от счастья после этих новостей.'],
            ['word' => 'When pigs fly', 'translation' => 'никогда', 'comment' => 'He’ll apologize when pigs fly - Он никогда не извинится.'],
            ['word' => 'Sit tight', 'translation' => 'подожди', 'comment' => 'Sit tight, I’ll be back - Подожди, я вернусь.'],
            ['word' => 'Be broke', 'translation' => 'быть без денег', 'comment' => 'I’m broke this month - В этом месяце я без денег.'],
            ['word' => 'Make ends meet', 'translation' => 'сводить концы с концами', 'comment' => 'It’s hard to make ends meet - Сложно сводить концы с концами.'],
            ['word' => 'A piece of the pie', 'translation' => 'доля прибыли', 'comment' => 'Everyone wants a piece of the pie - Все хотят иметь долю в прибыли.'],
            ['word' => 'Be on the same page', 'translation' => 'быть на одной волне', 'comment' => 'Let’s make sure we’re on the same page - Давай удостоверимся, что мы на одной волне.'],
            ['word' => 'Be in the same boat', 'translation' => 'быть в одной лодке', 'comment' => 'We’re all in the same boat - Мы все в одной лодке.'],
            ['word' => 'Keep an eye on', 'translation' => 'присматривать', 'comment' => 'Can you keep an eye on my bag? - Можешь присмотреть за моей сумкой?'],
            ['word' => 'A hot potato', 'translation' => 'спорная тема', 'comment' => 'That topic is a political hot potato - Эта тема в политике очень спорная.'],
            ['word' => 'A storm in a teacup', 'translation' => 'преувеличение', 'comment' => 'It’s just a storm in a teacup - Это преувеличение.'],
            ['word' => 'A blessing in disguise', 'translation' => 'нет худа без добра', 'comment' => 'It was a blessing in disguise - Не было худа без добра.'],
            ['word' => 'Cry over spilled milk', 'translation' => 'жалеть о прошлом', 'comment' => 'Don’t cry over spilled milk - Не жалей о прошлом.'],
            ['word' => 'The best of both worlds', 'translation' => 'лучшее из двух вариантов', 'comment' => 'Remote work gives me the best of both worlds - Удаленная работа — лучшее из возможных вариантов.'],
            ['word' => 'Turn a blind eye', 'translation' => 'закрывать глаза', 'comment' => 'He turned a blind eye to the mistake - Он закрыл глаза на ошибку.'],
        ];
    }
};
