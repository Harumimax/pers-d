@php
    $currentLanguage = session('ui_locale', 'ru');
    $targetLanguage = $currentLanguage === 'ru' ? 'en' : 'ru';
@endphp

<form method="POST" action="{{ route('interface-language.update') }}" class="language-switcher">
    @csrf
    <input type="hidden" name="language" value="{{ $targetLanguage }}">

    <button
        type="submit"
        class="language-switcher__toggle {{ $currentLanguage === 'en' ? 'language-switcher__toggle--en' : 'language-switcher__toggle--ru' }}"
        aria-label="Switch interface language"
    >
        <span class="language-switcher__label language-switcher__label--ru">Ru</span>
        <span class="language-switcher__thumb" aria-hidden="true"></span>
        <span class="language-switcher__label language-switcher__label--en">En</span>
    </button>
</form>
