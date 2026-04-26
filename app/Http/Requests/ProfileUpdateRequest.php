<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    private const CONTROL_CHARACTER_PATTERN = '/[\p{Cc}\p{Cf}]/u';
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';
    private const TG_LOGIN_PATTERN = '/^[A-Za-z0-9_]+$/';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeTextInput($this->input('name')),
            'email' => $this->sanitizeEmail($this->input('email')),
            'preferred_locale' => $this->sanitizeLocale($this->input('preferred_locale')),
            'tg_login' => $this->sanitizeTelegramLogin($this->input('tg_login')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            'email' => [
                'required',
                'string',
                'not_regex:'.self::CONTROL_CHARACTER_PATTERN,
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'preferred_locale' => [
                'required',
                'string',
                Rule::in(config('app.supported_locales', [config('app.locale')])),
            ],
            'tg_login' => [
                'nullable',
                'string',
                'max:255',
                'not_regex:'.self::CONTROL_CHARACTER_PATTERN,
                'regex:'.self::TG_LOGIN_PATTERN,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tg_login.regex' => __('validation.telegram_login'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('validation.attributes.name'),
            'email' => __('validation.attributes.email'),
            'preferred_locale' => __('validation.attributes.preferred_locale'),
            'tg_login' => __('validation.attributes.tg_login'),
        ];
    }

    private function sanitizeTextInput(?string $value): string
    {
        $normalized = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', (string) $value) ?? (string) $value;

        return trim($normalized);
    }

    private function sanitizeEmail(?string $value): string
    {
        return mb_strtolower($this->sanitizeTextInput($value));
    }

    private function sanitizeLocale(?string $value): string
    {
        return mb_strtolower($this->sanitizeTextInput($value));
    }

    private function sanitizeTelegramLogin(?string $value): ?string
    {
        $normalized = $this->sanitizeTextInput($value);
        $normalized = ltrim($normalized, '@');

        return $normalized === '' ? null : $normalized;
    }
}
