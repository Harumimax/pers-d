<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_language' => trim((string) $this->input('source_language')),
            'target_language' => trim((string) $this->input('target_language')),
            'text' => preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim((string) $this->input('text'))) ?? '',
        ]);
    }

    public function rules(): array
    {
        $supportedLanguages = ['ru', 'en', 'es', 'de', 'it', 'pt'];

        return [
            'source_language' => ['required', 'string', Rule::in($supportedLanguages)],
            'target_language' => ['required', 'string', Rule::in($supportedLanguages)],
            'text' => ['required', 'string', 'max:4500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'source_language' => __('translator.form.source_language'),
            'target_language' => __('translator.form.target_language'),
            'text' => __('translator.form.input_label'),
        ];
    }
}
