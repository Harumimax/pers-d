<?php

return [
    'meta_title' => 'WordKeeper — personal vocabulary workspace',
    'meta_description' => 'Collect foreign words, organize them into dictionaries, translate text, and review vocabulary in WordKeeper.',
    'skip_to_content' => 'Skip to main content',
    'actions' => [
        'open_dictionaries' => 'Open my dictionaries',
        'explore_ready' => 'Explore prepared dictionaries',
        'about' => 'About WordKeeper',
    ],
    'intro' => [
        'kicker' => 'A personal space for words',
        'title' => 'Keep the words you want to remember.',
        'text' => 'WordKeeper brings vocabulary, meanings, examples, and practice into one calm workspace. Build your own dictionaries or explore prepared collections before creating an account.',
        'note' => 'English, Spanish, German, Italian, Portuguese, and Russian vocabulary in one place.',
        'navigation_label' => 'Explore WordKeeper',
    ],
    'entry' => [
        'aria_label' => 'Example of a WordKeeper dictionary entry',
        'language' => 'English',
        'part_of_speech' => 'noun',
        'translation' => 'любознательность',
        'example_translation' => 'Любознательность превращает новое слово в знакомое.',
        'tags_label' => 'Information stored with the word',
        'tag_dictionary' => 'Personal dictionary',
        'tag_example' => 'Usage example',
        'tag_favorite' => 'Favorite',
    ],
    'capabilities' => [
        'kicker' => 'Inside WordKeeper',
        'title' => 'More than a list of translations',
        'text' => 'Each part of the application supports a real vocabulary task: collecting, understanding, finding, and reviewing words.',
        'account_required' => 'Available with an account',
        'items' => [
            'personal' => [
                'title' => 'Personal dictionaries',
                'text' => 'Group vocabulary by language, course, book, or any subject that makes sense to you.',
                'link' => 'Open dictionaries',
            ],
            'prepared' => [
                'title' => 'Prepared collections',
                'text' => 'Browse public word sets by language, level, and part of speech without registration.',
                'link' => 'Browse collections',
            ],
            'practice' => [
                'title' => 'Focused review',
                'text' => 'Choose dictionaries, parts of speech, direction, and session length. WordKeeper builds the round from your settings.',
                'link' => 'Open practice',
            ],
            'translation' => [
                'title' => 'Words and text translation',
                'text' => 'Translate individual words while adding them or work with a longer text in the built-in translator.',
            ],
            'telegram' => [
                'title' => 'Telegram companion',
                'text' => 'Browse dictionaries, add words, translate text, and complete review sessions in the bot.',
            ],
            'languages' => [
                'title' => 'Six vocabulary languages',
                'text' => 'Work with English, Spanish, German, Italian, Portuguese, and Russian dictionaries.',
            ],
        ],
    ],
    'workflow' => [
        'kicker' => 'A simple rhythm',
        'title' => 'A word has somewhere to go next',
        'text' => 'WordKeeper keeps the path from discovery to review short and visible.',
        'steps' => [
            'discover' => [
                'title' => 'Discover',
                'text' => 'Find a word while reading, studying, or speaking.',
            ],
            'organize' => [
                'title' => 'Save with context',
                'text' => 'Keep the translation, part of speech, comment, and usage examples together.',
            ],
            'practice' => [
                'title' => 'Return to it',
                'text' => 'Use a focused web or Telegram session when it is time to review.',
            ],
        ],
        'image_alt' => 'WordKeeper dictionary interface showing words and their translations',
        'caption' => 'A real dictionary view: vocabulary, translations, context, and actions stay together.',
    ],
    'ready' => [
        'kicker' => 'Open to everyone',
        'title' => 'Start with a prepared dictionary',
        'text' => 'Public collections are available without registration and can be filtered by language, level, and part of speech.',
        'view_all' => 'View all prepared dictionaries',
        'empty_title' => 'Collections are being prepared',
        'empty_text' => 'The full catalog will appear here as soon as prepared dictionaries are available.',
    ],
];
