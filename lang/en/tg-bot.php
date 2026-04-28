<?php

return [
    'title' => 'TG bot',
    'subtitle' => 'Configure how WordKeeper will send practice sessions to Telegram. This section stores only the mode settings. The actual Telegram gameplay will be added in the next steps.',
    'bot_address_title' => 'Bot address',
    'bot_address_text' => 'You can open the bot at',
    'connection' => [
        'title' => 'Telegram connection',
        'connected' => 'Authorized in bot',
        'not_connected' => 'Not authorized in bot',
        'required_title' => 'Authorization required',
        'required_to_configure' => 'Telegram session settings become available only after you authorize in the bot.',
    ],
    'form' => [
        'saved' => 'Telegram settings were saved.',
        'save' => 'Save',
        'errors_title' => 'Please review the form errors.',
        'timezone' => [
            'label' => 'Timezone',
            'hint' => 'This timezone will be used for all Telegram modes configured on this page.',
        ],
        'random_words' => [
            'title' => 'Send random words to Telegram',
            'description' => 'The bot will use the multiple choice format with 6 answer options and will later send scheduled practice sessions directly to Telegram.',
            'enabled' => 'Mode status',
            'choice_hint' => 'This mode always uses the 6-option multiple choice format.',
        ],
        'directions' => [
            'foreign_to_ru' => 'Foreign language to Russian',
            'ru_to_foreign' => 'Russian to foreign language',
        ],
        'sessions' => [
            'title' => 'Sessions per day',
            'hint' => 'Add from 1 to 5 daily sessions. Each session stores its own time, translation direction, part of speech filter, and dictionaries.',
            'first_title' => '1st session',
            'additional_title_prefix' => 'Session',
            'session_hint' => 'The bot will use these settings when this session is implemented in Telegram delivery.',
            'words_count_hint' => 'Choose from 2 to 20 words for this session.',
            'fields' => [
                'send_time' => 'Send time',
                'translation_direction' => 'Translation direction',
                'words_count' => 'Words per session',
                'part_of_speech' => 'Parts of speech',
                'user_dictionaries' => 'My dictionaries',
                'ready_dictionaries' => 'Prepared dictionaries',
            ],
            'empty_user_dictionaries' => 'No personal dictionaries yet.',
            'empty_ready_dictionaries' => 'No prepared dictionaries available yet.',
        ],
    ],
    'validation' => [
        'session_requires_dictionary' => 'Session :number must include at least one personal or prepared dictionary.',
    ],
];
