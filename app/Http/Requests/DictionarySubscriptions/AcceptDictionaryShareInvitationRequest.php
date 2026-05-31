<?php

namespace App\Http\Requests\DictionarySubscriptions;

use Illuminate\Foundation\Http\FormRequest;

class AcceptDictionaryShareInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
