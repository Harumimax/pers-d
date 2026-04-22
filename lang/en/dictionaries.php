<?php

return [
    'index' => [
        'title' => 'My Dictionaries',
        'subtitle' => 'Manage your foreign word collections',
        'new_dictionary' => 'New Dictionary',
        'create_form_aria' => 'Create dictionary form',
        'fields' => [
            'name' => 'Dictionary Name',
            'language' => 'Language',
        ],
        'placeholders' => [
            'name' => 'e.g., Italian Basics',
        ],
        'language_prompt' => 'Select language',
        'languages' => [
            'english' => 'English',
            'spanish' => 'Spanish',
            'not_specified' => 'Language not specified',
        ],
        'actions' => [
            'create' => 'Create',
            'cancel' => 'Cancel',
        ],
        'meta' => [
            'created' => 'Created :date',
        ],
        'words_count' => '{1} :count word|[2,*] :count words',
        'empty' => [
            'title' => 'No dictionaries yet',
            'text' => 'Create your first dictionary to start organizing words.',
        ],
        'card' => [
            'open_aria' => 'Open dictionary :name',
        ],
        'edit' => [
            'aria' => 'Edit dictionary :name',
            'field_aria' => 'Dictionary name for :name',
            'accept' => 'Apply',
            'cancel' => 'Cancel',
        ],
        'delete' => [
            'aria' => 'Delete dictionary :name',
            'title' => 'Delete Dictionary',
            'text' => 'Are you sure you want to delete ":name"?',
            'no' => 'No',
            'yes' => 'Yes',
        ],
    ],
    'show' => [
        'add_word' => 'Add Word',
        'subtitle' => 'Language :language · Total words: :count · Created on :date',
        'unknown_date' => 'unknown date',
        'not_specified' => 'not specified',
        'add_word_form_aria' => 'Add word form',
        'add_word_mode' => 'Add word mode',
        'modes' => [
            'automatic' => 'Translate automatically',
            'manual' => 'Enter manually',
        ],
        'fields' => [
            'word' => 'Word',
            'part_of_speech' => 'Part of speech',
            'translation' => 'Translation',
            'comment' => 'Comment',
            'selected_translation' => 'Selected translation',
            'action' => 'Action',
        ],
        'placeholders' => [
            'word' => 'e.g., buongiorno',
            'translation' => 'e.g., good morning',
            'comment' => 'e.g., formal greeting',
            'part_of_speech' => 'Select part of speech',
            'search' => 'Search word or translation...',
        ],
        'actions' => [
            'add' => 'Add',
            'cancel' => 'Cancel',
            'translate' => 'Translate',
            'translating' => 'Translating...',
            'switch_to_manual' => 'Switch to Enter manually',
        ],
        'translation' => [
            'unavailable' => 'Translation is currently unavailable. Please switch to Enter manually.',
            'suggested_title' => 'Suggested translations',
            'suggested_subtitle' => 'Choose the most suitable translation for this dictionary',
            'selected_translation_empty' => 'Choose a translation from the suggestions above',
        ],
        'word_list' => [
            'title' => 'Word List',
            'subtitle' => '{1} :count word in this dictionary|[2,*] :count words in this dictionary',
            'search_hint' => 'Press Enter to search',
            'filter_aria' => 'Filter words by part of speech',
            'sort_aria' => 'Sort words',
            'sort' => [
                'newest' => 'Newest first',
                'a_z' => 'A-Z',
                'oldest' => 'Oldest first',
            ],
            'empty' => 'No words yet. Add your first word using the form above.',
            'table' => [
                'word' => 'Word',
                'translation' => 'Translation',
                'comment' => 'Comment',
                'added' => 'Added',
                'action' => 'Action',
            ],
            'part_of_speech_not_specified' => 'Part of speech not specified',
            'no_comment' => 'No comment',
            'remainder_mistake_marker_aria' => 'Previous Remainder mistake',
            'remainder_mistake_legend' => 'The red dot means you previously made a mistake with this word in the Remainder game.',
            'pagination' => [
                'showing' => 'Showing :from-:to of :total words',
                'prev' => 'Prev',
                'next' => 'Next',
            ],
            'delete' => [
                'aria' => 'Delete word :name',
                'title' => 'Delete Word',
                'text' => 'Are you sure you want to delete ":name"?',
                'no' => 'No',
                'yes' => 'Yes',
            ],
            'edit' => [
                'aria' => 'Edit word :name',
                'accept' => 'Apply',
                'cancel' => 'Cancel',
            ],
        ],
    ],
];
