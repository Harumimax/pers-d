<?php

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTelegramRandomWordsStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'random_words_enabled' => $this->boolean('random_words_enabled'),
        ]);
    }

    public function rules(): array
    {
        return [
            'random_words_enabled' => ['required', 'boolean'],
        ];
    }
}
