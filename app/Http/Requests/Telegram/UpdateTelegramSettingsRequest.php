<?php

namespace App\Http\Requests\Telegram;

use App\Models\GameSession;
use App\Support\PartOfSpeechCatalog;
use DateTimeZone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTelegramSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $sessions = collect($this->input('sessions', []))
            ->map(function ($session): array {
                $normalized = is_array($session) ? $session : [];

                return [
                    'send_time' => trim((string) ($normalized['send_time'] ?? '')),
                    'translation_direction' => trim((string) ($normalized['translation_direction'] ?? '')),
                    'part_of_speech' => collect($normalized['part_of_speech'] ?? [])
                        ->map(fn ($value) => trim((string) $value))
                        ->filter()
                        ->values()
                        ->all(),
                    'user_dictionary_ids' => collect($normalized['user_dictionary_ids'] ?? [])
                        ->map(fn ($value) => (int) $value)
                        ->filter(fn (int $value) => $value > 0)
                        ->values()
                        ->all(),
                    'ready_dictionary_ids' => collect($normalized['ready_dictionary_ids'] ?? [])
                        ->map(fn ($value) => (int) $value)
                        ->filter(fn (int $value) => $value > 0)
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'timezone' => trim((string) $this->input('timezone')),
            'random_words_enabled' => $this->boolean('random_words_enabled'),
            'sessions' => $sessions,
        ]);
    }

    public function rules(): array
    {
        return [
            'timezone' => [
                'required',
                'string',
                Rule::in(DateTimeZone::listIdentifiers()),
            ],
            'random_words_enabled' => ['required', 'boolean'],
            'sessions' => ['required', 'array', 'min:1', 'max:5'],
            'sessions.*.send_time' => ['required', 'date_format:H:i'],
            'sessions.*.translation_direction' => [
                'required',
                'string',
                Rule::in([
                    GameSession::DIRECTION_FOREIGN_TO_RU,
                    GameSession::DIRECTION_RU_TO_FOREIGN,
                ]),
            ],
            'sessions.*.part_of_speech' => ['nullable', 'array'],
            'sessions.*.part_of_speech.*' => [
                'string',
                Rule::in(PartOfSpeechCatalog::valuesWithAll()),
            ],
            'sessions.*.user_dictionary_ids' => ['nullable', 'array'],
            'sessions.*.user_dictionary_ids.*' => [
                'integer',
                Rule::exists('user_dictionaries', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'sessions.*.ready_dictionary_ids' => ['nullable', 'array'],
            'sessions.*.ready_dictionary_ids.*' => [
                'integer',
                Rule::exists('ready_dictionaries', 'id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('sessions', []) as $index => $session) {
                $userDictionaryIds = collect($session['user_dictionary_ids'] ?? [])
                    ->filter()
                    ->all();
                $readyDictionaryIds = collect($session['ready_dictionary_ids'] ?? [])
                    ->filter()
                    ->all();

                if ($userDictionaryIds === [] && $readyDictionaryIds === []) {
                    $validator->errors()->add(
                        "sessions.{$index}.user_dictionary_ids",
                        __('tg-bot.validation.session_requires_dictionary', ['number' => $index + 1])
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'timezone' => __('tg-bot.form.timezone.label'),
            'random_words_enabled' => __('tg-bot.form.random_words.enabled'),
            'sessions' => __('tg-bot.form.sessions.title'),
            'sessions.*.send_time' => __('tg-bot.form.sessions.fields.send_time'),
            'sessions.*.translation_direction' => __('tg-bot.form.sessions.fields.translation_direction'),
            'sessions.*.part_of_speech' => __('tg-bot.form.sessions.fields.part_of_speech'),
            'sessions.*.user_dictionary_ids' => __('tg-bot.form.sessions.fields.user_dictionaries'),
            'sessions.*.ready_dictionary_ids' => __('tg-bot.form.sessions.fields.ready_dictionaries'),
        ];
    }
}
