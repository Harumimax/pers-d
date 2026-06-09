@extends('layouts.profile', ['activeNav' => 'translator'])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\VersionedAsset::url('css/translator.css') }}">
@endpush

@section('content')
    <main class="translator-main">
        <div class="translator-container">
            <header class="translator-header">
                <h1 class="translator-title">{{ __('translator.title') }}</h1>
                <p class="translator-description">{{ __('translator.description') }}</p>
            </header>

            @if ($translationError !== null)
                <div class="translator-alert translator-alert--error" role="alert">
                    {{ $translationError }}
                </div>
            @endif

            @if ($errors->any())
                <div class="translator-alert translator-alert--error" role="alert">
                    <p class="translator-alert__title">{{ __('translator.messages.validation_failed') }}</p>
                    <ul class="translator-alert__list">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="translator-card">
                <form
                    method="POST"
                    action="{{ route('translator.store') }}"
                    class="translator-form"
                    x-data="{ charCount: {{ mb_strlen(old('text', $formData['text'])) }} }"
                >
                    @csrf

                    <div class="translator-direction-row">
                        <div class="translator-field translator-field--compact">
                            <label for="source-language" class="translator-label">{{ __('translator.form.translate_from') }}</label>
                            <select id="source-language" name="source_language" class="translator-select">
                                @foreach ($languageOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('source_language', $formData['source_language']) === $option['value'])>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="translator-field translator-field--compact">
                            <label for="target-language" class="translator-label">{{ __('translator.form.translate_to') }}</label>
                            <select id="target-language" name="target_language" class="translator-select">
                                @foreach ($languageOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('target_language', $formData['target_language']) === $option['value'])>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="translator-field">
                        <div class="translator-field__header">
                            <label for="translator-input" class="translator-label">{{ __('translator.form.input_label') }}</label>
                            <span class="translator-counter">
                                <span x-text="charCount"></span>
                                {{ __('translator.form.characters_counter_suffix', ['limit' => 4500]) }}
                            </span>
                        </div>
                        <textarea
                            id="translator-input"
                            name="text"
                            class="translator-textarea"
                            maxlength="4500"
                            placeholder="{{ __('translator.form.input_placeholder') }}"
                            x-on:input="charCount = $event.target.value.length"
                        >{{ old('text', $formData['text']) }}</textarea>
                    </div>

                    <div class="translator-actions">
                        <button type="submit" class="btn btn-primary translator-submit-btn">
                            {{ __('translator.form.submit') }}
                        </button>
                    </div>

                    <div class="translator-field">
                        <label for="translator-result" class="translator-label">{{ __('translator.form.result_label') }}</label>
                        <textarea
                            id="translator-result"
                            class="translator-textarea translator-textarea--result"
                            readonly
                            placeholder="{{ __('translator.form.result_placeholder') }}"
                        >{{ $translatedText }}</textarea>
                    </div>
                </form>
            </section>
        </div>
    </main>
@endsection
