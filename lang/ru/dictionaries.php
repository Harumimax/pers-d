<?php

return [
    'index' => [
        'title' => 'Мои словари',
        'subtitle' => 'Управляйте своими коллекциями иностранных слов',
        'new_dictionary' => 'Новый словарь',
        'create_form_aria' => 'Форма создания словаря',
        'fields' => [
            'name' => 'Название словаря',
            'language' => 'Язык',
        ],
        'placeholders' => [
            'name' => 'например, Italian Basics',
        ],
        'language_prompt' => 'Выберите язык',
        'languages' => [
            'english' => 'Английский',
            'spanish' => 'Испанский',
            'not_specified' => 'Язык не указан',
        ],
        'actions' => [
            'create' => 'Создать',
            'cancel' => 'Отмена',
        ],
        'meta' => [
            'created' => 'Создан :date',
        ],
        'words_count' => '{1} :count слово|[2,4] :count слова|[5,*] :count слов',
        'empty' => [
            'title' => 'Словарей пока нет',
            'text' => 'Создайте свой первый словарь, чтобы начать организовывать слова.',
        ],
        'edit' => [
            'aria' => 'Редактировать словарь :name',
            'field_aria' => 'Название словаря :name',
            'accept' => 'Принять',
            'cancel' => 'Отмена',
        ],
        'delete' => [
            'aria' => 'Удалить словарь :name',
            'title' => 'Удаление словаря',
            'text' => 'Вы уверены, что хотите удалить ":name"?',
            'no' => 'Нет',
            'yes' => 'Да',
        ],
    ],
    'show' => [
        'add_word' => 'Добавить слово',
        'subtitle' => 'Язык :language · Всего слов: :count · Создан :date',
        'unknown_date' => 'дата неизвестна',
        'not_specified' => 'не указан',
        'add_word_form_aria' => 'Форма добавления слова',
        'add_word_mode' => 'Режим добавления слова',
        'modes' => [
            'automatic' => 'Перевести автоматически',
            'manual' => 'Ввести вручную',
        ],
        'fields' => [
            'word' => 'Слово',
            'part_of_speech' => 'Часть речи',
            'translation' => 'Перевод',
            'comment' => 'Комментарий',
            'selected_translation' => 'Выбранный перевод',
            'action' => 'Действие',
        ],
        'placeholders' => [
            'word' => 'например, buongiorno',
            'translation' => 'например, good morning',
            'comment' => 'например, formal greeting',
            'part_of_speech' => 'Выберите часть речи',
            'search' => 'Поиск по слову или переводу...',
        ],
        'actions' => [
            'add' => 'Добавить',
            'cancel' => 'Отмена',
            'translate' => 'Перевести',
            'translating' => 'Перевожу...',
            'switch_to_manual' => 'Переключиться на ручной ввод',
        ],
        'translation' => [
            'unavailable' => 'Перевод сейчас недоступен. Пожалуйста, переключитесь на ручной ввод.',
            'suggested_title' => 'Предлагаемые переводы',
            'suggested_subtitle' => 'Выберите наиболее подходящий перевод для этого словаря',
            'selected_translation_empty' => 'Выберите перевод из предложенных выше вариантов',
        ],
        'word_list' => [
            'title' => 'Список слов',
            'subtitle' => '{1} :count слово в этом словаре|[2,4] :count слова в этом словаре|[5,*] :count слов в этом словаре',
            'search_hint' => 'Нажмите Enter для поиска',
            'filter_aria' => 'Фильтр слов по части речи',
            'sort_aria' => 'Сортировка слов',
            'sort' => [
                'newest' => 'Сначала новые',
                'a_z' => 'А-Я',
                'oldest' => 'Сначала старые',
            ],
            'empty' => 'Слов пока нет. Добавьте первое слово через форму выше.',
            'table' => [
                'word' => 'Слово',
                'translation' => 'Перевод',
                'comment' => 'Комментарий',
                'added' => 'Добавлено',
                'action' => 'Действие',
            ],
            'part_of_speech_not_specified' => 'Часть речи не указана',
            'no_comment' => 'Комментария нет',
            'pagination' => [
                'showing' => 'Показано :from-:to из :total слов',
                'prev' => 'Назад',
                'next' => 'Далее',
            ],
            'delete' => [
                'aria' => 'Удалить слово :name',
                'title' => 'Удаление слова',
                'text' => 'Вы уверены, что хотите удалить ":name"?',
                'no' => 'Нет',
                'yes' => 'Да',
            ],
        ],
    ],
];


