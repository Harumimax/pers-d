<?php

namespace App\Http\Requests;

use App\Models\GameSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartManualGameRequest extends FormRequest
{
    private const PARTS_OF_SPEECH = [
        'all',
        'noun',
        'verb',
        'adjective',
        'adverb',
        'pronoun',
        'cardinal',
        'preposition',
        'conjunction',
        'interjection',
        'stable_expression',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $dictionaryIds = array_values(array_filter((array) $this->input('dictionary_ids'), static fn ($value) => $value !== null && $value !== ''));
        $partsOfSpeech = array_values(array_filter((array) $this->input('parts_of_speech'), static fn ($value) => $value !== null && $value !== ''));

        $this->merge([
            'mode' => trim((string) $this->input('mode')),
            'direction' => trim((string) $this->input('direction')),
            'words_count' => is_numeric($this->input('words_count')) ? (int) $this->input('words_count') : $this->input('words_count'),
            'dictionary_ids' => $dictionaryIds,
            'parts_of_speech' => $partsOfSpeech === [] ? ['all'] : $partsOfSpeech,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', Rule::in([GameSession::MODE_MANUAL])],
            'direction' => ['required', 'string', Rule::in([
                GameSession::DIRECTION_FOREIGN_TO_RU,
                GameSession::DIRECTION_RU_TO_FOREIGN,
            ])],
            'dictionary_ids' => ['required', 'array', 'min:1'],
            'dictionary_ids.*' => ['integer', 'distinct'],
            'parts_of_speech' => ['required', 'array', 'min:1'],
            'parts_of_speech.*' => ['string', Rule::in(self::PARTS_OF_SPEECH)],
            'words_count' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.in' => 'Only manual translation input is available right now.',
            'dictionary_ids.required' => 'Choose at least one dictionary to start the game.',
            'words_count.max' => 'Words count cannot be greater than 20.',
        ];
    }
}
