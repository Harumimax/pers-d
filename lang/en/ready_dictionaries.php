<?php

return [
    'title' => 'Ready dictionaries',
    'subtitle' => 'Curated dictionaries for future practice',
    'description' => 'This page will collect prepared dictionaries that are available to all signed-in users. In future updates, you will be able to use them in Remainder practice sessions or copy useful words into your own dictionaries.',
    'list_aria' => 'Ready dictionaries list',
    'filters' => [
        'aria' => 'Ready dictionaries filters',
        'language' => 'Language',
        'level' => 'Level',
        'part_of_speech' => 'Part of speech',
        'all_languages' => 'All languages',
        'all_levels' => 'All levels',
        'all_parts_of_speech' => 'All parts of speech',
        'apply' => 'Apply',
        'reset' => 'Reset',
    ],
    'empty' => [
        'title' => 'No ready dictionaries found',
        'text' => 'Try changing the filters or come back later when more ready dictionaries are added.',
    ],
    'card' => [
        'open_aria' => 'Open ready dictionary :name',
    ],
    'show' => [
        'subtitle' => 'A ready dictionary in :language with :count words. Created :date.',
        'transfer' => [
            'aria' => 'Choose a personal dictionary for :word',
            'title' => 'Add to dictionary',
            'empty' => 'Create your own dictionary to add a word to it.',
            'success' => '":word" has been added to ":dictionary".',
            'error' => 'We could not add this word to the selected dictionary. Please try again.',
        ],
    ],
];
