<?php

return [
    'menu' => 'Переводчик',
    'title' => 'Переводите короткие тексты прямо в WordKeeper.',
    'description' => 'Используйте текущий стек провайдеров перевода, чтобы перевести фразу, предложение или короткий текст объёмом до 4500 символов.',
    'form' => [
        'translate_from' => 'Перевести с',
        'translate_to' => 'На',
        'source_language' => 'язык источника',
        'target_language' => 'язык перевода',
        'input_label' => 'Введите текст',
        'input_placeholder' => 'Введите или вставьте текст сюда...',
        'result_label' => 'Результат перевода',
        'result_placeholder' => 'Здесь появится переведённый текст.',
        'characters_counter_suffix' => 'символов из :limit',
        'submit' => 'Перевести',
    ],
    'messages' => [
        'failed' => 'LibreTranslate временно недоступен. Пожалуйста, попробуйте позже.',
        'validation_failed' => 'Исправьте ошибки в форме и попробуйте снова.',
    ],
];
