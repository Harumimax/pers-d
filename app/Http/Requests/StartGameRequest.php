<?php

namespace App\Http\Requests;

use App\Models\GameSession;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dictionaryIds = array_values(array_filter((array) $this->input('dictionary_ids'), static fn ($value) => $value !== null && $value !== ''));
        $readyDictionaryIds = array_values(array_filter((array) $this->input('ready_dictionary_ids'), static fn ($value) => $value !== null && $value !== ''));
        $partsOfSpeech = array_values(array_filter((array) $this->input('parts_of_speech'), static fn ($value) => $value !== null && $value !== ''));

        $this->merge([
            'mode' => trim((string) $this->input('mode')),
            'direction' => trim((string) $this->input('direction')),
            'words_count' => is_numeric($this->input('words_count')) ? (int) $this->input('words_count') : $this->input('words_count'),
            'dictionary_ids' => $dictionaryIds,
            'ready_dictionary_ids' => $readyDictionaryIds,
            'parts_of_speech' => $partsOfSpeech === [] ? ['all'] : $partsOfSpeech,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', Rule::in([
                GameSession::MODE_MANUAL,
                GameSession::MODE_CHOICE,
            ])],
            'direction' => ['required', 'string', Rule::in([
                GameSession::DIRECTION_FOREIGN_TO_RU,
                GameSession::DIRECTION_RU_TO_FOREIGN,
            ])],
            'dictionary_ids' => ['nullable', 'array'],
            'dictionary_ids.*' => ['integer', 'distinct'],
            'ready_dictionary_ids' => ['nullable', 'array'],
            'ready_dictionary_ids.*' => ['integer', 'distinct', Rule::exists('ready_dictionaries', 'id')],
            'parts_of_speech' => ['required', 'array', 'min:1'],
            'parts_of_speech.*' => ['string', Rule::in(PartOfSpeechCatalog::valuesWithAll())],
            'words_count' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('dictionary_ids') || $validator->errors()->has('ready_dictionary_ids')) {
                    return;
                }

                if ($this->user() === null && $this->input('dictionary_ids', []) !== []) {
                    $validator->errors()->add('dictionary_ids', __('remainder.messages.start.demo_user_dictionaries'));

                    return;
                }

                if ($this->input('dictionary_ids', []) === [] && $this->input('ready_dictionary_ids', []) === []) {
                    $validator->errors()->add('dictionary_ids', __('remainder.messages.start.choose_dictionary'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dictionary_ids.required' => __('remainder.messages.start.choose_dictionary'),
            'words_count.max' => __('remainder.messages.start.words_count_max'),
        ];
    }

    public function attributes(): array
    {
        return [
            'dictionary_ids' => __('validation.attributes.dictionary_ids'),
            'ready_dictionary_ids' => __('validation.attributes.ready_dictionary_ids'),
            'words_count' => __('validation.attributes.words_count'),
        ];
    }
}
