<?php

return [
    'hero' => [
        'eyebrow' => 'About WordKeeper',
        'title' => 'A calm workspace for building your personal foreign-word dictionaries.',
        'image_alt' => 'Open dictionary book',
        'description_1' => 'WordKeeper is a personal vocabulary site for people who want one neat place to collect foreign words, keep translations close at hand, and organize learning by separate dictionaries. The product is intentionally focused: instead of spreading words across notes, chats, and browser tabs, you keep them in one structured space with clean navigation and a lightweight workflow.',
        'description_2' => 'Right now the site already supports the core product loop: authenticated users can create personal dictionaries, add words manually or through assisted translation, assign part of speech, keep comments, launch Remainder practice sessions, and work with the interface in Russian or English. The current focus is polishing the existing learning workflow and connecting the remaining delivery pieces such as real contact email handling.',
    ],
    'contact' => [
        'title' => 'Contact form',
        'subtitle' => 'Share a question, suggestion, or bug report directly from the page.',
        'email' => 'Contact email',
        'subject' => 'Subject',
        'message' => 'Message',
        'email_placeholder' => 'you@example.com',
        'subject_placeholder' => 'What would you like to discuss?',
        'message_placeholder' => 'Write your message here...',
        'send' => 'Send',
        'clear' => 'Clear all',
        'status' => [
            'success' => 'Your message has been sent successfully.',
            'error' => 'We could not send your message right now. Please try again a little later.',
        ],
        'mail' => [
            'title' => 'New message from the About page contact form',
            'subject_line' => '[WordKeeper] About contact form: :subject',
        ],
    ],
    'general_statistics' => [
        'title' => 'General statistics',
        'subtitle' => 'A quick snapshot of aggregate activity across the whole site.',
        'rows' => [
            'dictionaries_count' => 'Total dictionaries across all users',
            'word_entries_count' => 'Total word entries across all user dictionaries',
            'sessions_count' => 'Total game sessions played by all users',
            'accuracy_percentage' => 'Overall correct answers percentage across all games',
        ],
        'fallbacks' => [
            'no_answers' => 'No answers yet',
        ],
    ],
    'legal' => [
        'privacy' => [
            'title' => 'Privacy Policy and Personal Data Processing',
        ],
        'cookies' => [
            'title' => 'Cookie Policy',
        ],
    ],
    'status' => [
        'title' => 'Current functionality',
        'subtitle' => 'What is already available in the product and what is being built next.',
        'columns' => [
            'functionality' => 'Functionality',
            'status' => 'Status',
        ],
        'badges' => [
            'done' => 'done',
            'planning' => 'planning',
        ],
        'items' => [
            'manage_dictionaries' => 'Create and manage personal dictionaries',
            'manual_words' => 'Add words manually with translation, part of speech, and comment',
            'search_filter_sort' => 'Search, filter, sort, and paginate words inside a dictionary',
            'translation_suggestions' => 'Automatic translation suggestions during word creation',
            'delete_confirmations' => 'Delete dictionaries and words with confirmation dialogs',
            'manual_remainder' => 'Play Remainder sessions with manual translation input',
            'choice_remainder' => 'Play Remainder sessions in multiple choice mode',
            'profile_statistics' => 'Track personal Remainder statistics on the profile page',
            'snapshot_part_of_speech' => 'Store part of speech as part of the game session snapshot',
            'about_contact_form' => 'Add a collapsible contact form section on the About page',
            'language_switcher' => 'Switch the interface between Russian and English',
            'preferred_locale' => 'Remember a preferred interface language for authenticated users',
            'localized_flows' => 'Localize auth, welcome, and product flows in Russian and English',
            'telegram_bot' => 'Create a Telegram bot',
            'telegram_integration' => 'Connect site functionality to the Telegram bot',
            'telegram_send_mode' => 'Create a mode for sending words to the Telegram bot',
            'local_translation_provider' => 'Switch to another local translation provider',
            'real_contact_delivery' => 'Connect the About page contact form to real email delivery',
            'game_visuals' => 'Make the game interface more varied with alternate progress images and memes',
            'logo' => 'Create a WordKeeper logo',
        ],
    ],
];
