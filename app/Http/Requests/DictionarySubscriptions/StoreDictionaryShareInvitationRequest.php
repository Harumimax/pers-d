<?php

namespace App\Http\Requests\DictionarySubscriptions;

use Illuminate\Foundation\Http\FormRequest;

class StoreDictionaryShareInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('target_email', ''));

        $this->merge([
            'target_email' => mb_strtolower($email),
        ]);
    }
}
