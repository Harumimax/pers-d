<?php

namespace Tests\Unit;

use App\Support\DictionaryLanguageCode;
use Tests\TestCase;

class DictionaryLanguageCodeTest extends TestCase
{
    public function test_it_maps_supported_dictionary_languages_to_language_codes(): void
    {
        $this->assertSame('en', DictionaryLanguageCode::fromDictionaryLanguage('English'));
        $this->assertSame('es', DictionaryLanguageCode::fromDictionaryLanguage('Spanish'));
        $this->assertSame('de', DictionaryLanguageCode::fromDictionaryLanguage('German'));
        $this->assertSame('it', DictionaryLanguageCode::fromDictionaryLanguage('Italian'));
        $this->assertSame('pt', DictionaryLanguageCode::fromDictionaryLanguage('Portuguese'));
    }

    public function test_it_returns_null_for_unsupported_or_invalid_language(): void
    {
        $this->assertNull(DictionaryLanguageCode::fromDictionaryLanguage('French'));
        $this->assertNull(DictionaryLanguageCode::fromDictionaryLanguage(null));
    }
}
