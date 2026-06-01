<?php

return [
    'menu' => 'Translator',
    'title' => 'Translate short texts inside WordKeeper.',
    'description' => 'Use the current translation provider stack to translate a phrase, sentence, or short text up to 4500 characters.',
    'form' => [
        'translate_from' => 'Translate from',
        'translate_to' => 'To',
        'source_language' => 'source language',
        'target_language' => 'target language',
        'input_label' => 'Enter text',
        'input_placeholder' => 'Type or paste your text here...',
        'result_label' => 'Translation result',
        'result_placeholder' => 'The translated text will appear here.',
        'characters_counter_suffix' => 'characters out of :limit',
        'submit' => 'Translate',
    ],
    'messages' => [
        'failed' => 'We could not translate this text right now. Please try again a little later.',
        'validation_failed' => 'Please fix the highlighted validation issues and try again.',
    ],
];
