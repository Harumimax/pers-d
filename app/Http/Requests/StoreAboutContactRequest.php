<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAboutContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:128'],
            'message' => ['required', 'string', 'max:600'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contact_email' => __('validation.attributes.contact_email'),
            'subject' => __('validation.attributes.subject'),
            'message' => __('validation.attributes.message'),
        ];
    }
}
