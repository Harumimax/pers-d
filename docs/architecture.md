# Project Architecture

## Summary
- Stack: Laravel 13 + Blade + Livewire 4 + PostgreSQL
- Main domain: authenticated users create personal dictionaries and add words to them
- UI architecture: page-level dictionary screens are implemented as Livewire components, while auth/profile flows use classic Laravel controllers + Blade views
- Translation integration: external translation is isolated behind a service abstraction and is not called directly from Blade or controllers

## Main Layers

### Routing
- `routes/web.php` is the main web entrypoint
- Public route:
  - `/` -> `welcome` view
- Authenticated routes:
  - `/dashboard` -> redirects to dictionaries index
  - `/profile` -> `ProfileController`
  - `/about` -> auth-only informational page rendered from `about` view
  - `/remainder` -> auth-only placeholder page rendered from `remainder` view
  - `/dictionaries` -> `App\Livewire\Dictionaries\Index`
  - `/dictionaries/{dictionary}` -> `App\Livewire\Dictionaries\Show`

### Controllers
- `App\Http\Controllers\ProfileController`
  - edits profile
  - updates profile
  - deletes account
- Auth controllers are the standard Breeze-style controllers under `app/Http/Controllers/Auth`
- Dictionaries are not handled by traditional controllers; they are handled by Livewire page components

### Livewire Pages
- `App\Livewire\Dictionaries\Index`
  - lists current user dictionaries
  - creates dictionaries
  - deletes dictionaries with confirmation modal state
  - passes `headerDictionaries` into the shared dictionaries layout
- `App\Livewire\Dictionaries\Show`
  - shows a single dictionary
  - lists words with pagination
  - supports search, sorting, and part-of-speech filtering
  - supports manual word creation
  - supports automatic translation flow
  - deletes words with confirmation modal state
  - passes `headerDictionaries` into the shared dictionaries layout

### Views and Layouts
- Shared dictionaries layout: `resources/views/layouts/dictionaries.blade.php`
- Shared profile-style authenticated layout: `resources/views/layouts/profile.blade.php`
- Dictionary pages:
  - `resources/views/livewire/dictionaries/index.blade.php`
  - `resources/views/livewire/dictionaries/show.blade.php`
- Informational page:
  - `resources/views/about.blade.php`
  - `resources/views/remainder.blade.php`
- Key styling:
  - `public/css/dictionaries.css`
  - `public/css/dictionary-show.css`
  - `public/css/profile.css`
  - `public/css/footer.css`
  - `public/css/about.css`
  - `public/css/remainder.css`
- Important design choice:
  - the shared dictionaries layout is intentionally "dumb" and does not query the database itself
  - data needed by the layout is passed from Livewire components
  - the shared profile-style layout is reused by both `/profile` and `/about`, and the footer there links to the auth-only About page
  - both authenticated layouts render the same dictionaries hover-dropdown in the header, and the dropdown data is passed from controllers/Livewire rather than queried inside Blade

### Services
- Translation integration lives under `app/Services/Translation`
- Current abstraction:
  - `TranslationServiceInterface`
  - `MyMemoryTranslationService`
- Supporting DTOs:
  - `TranslationResult`
  - `TranslationSuggestion`
- Service binding is registered in `AppServiceProvider`
- External service configuration is stored in `config/services.php`

## Domain Model

### User
- Model: `App\Models\User`
- Relationship:
  - `hasMany(UserDictionary::class)` via `dictionaries()`

### UserDictionary
- Model: `App\Models\UserDictionary`
- Fillable:
  - `user_id`
  - `name`
  - `language`
- Relationships:
  - `belongsTo(User::class)` via `user()`
  - `belongsToMany(Word::class)` via `words()`

### Word
- Model: `App\Models\Word`
- Fillable:
  - `word`
  - `part_of_speech`
  - `translation`
  - `comment`
- Relationship:
  - `belongsToMany(UserDictionary::class)` via `dictionaries()`

## Database Structure

### Core Tables

#### `users`
- Standard Laravel users table
- Important fields:
  - `id`
  - `name`
  - `email`
  - `password`
  - `email_verified_at`

#### `user_dictionaries`
- Created in `2026_03_31_000003_create_user_dictionaries_table.php`
- Extended in `2026_04_03_000006_add_language_to_user_dictionaries_table.php`
- Fields:
  - `id`
  - `user_id` -> FK to `users.id`
  - `name`
  - `language` nullable
  - `created_at`
  - `updated_at`
- Constraints:
  - index on `user_id`
  - unique composite key on `user_id + name`

#### `words`
- Created in `2026_03_31_000004_create_words_table.php`
- Extended in `2026_04_06_000007_add_part_of_speech_to_words_table.php`
- Fields:
  - `id`
  - `word`
  - `part_of_speech` nullable
  - `translation`
  - `comment` nullable
  - `created_at`
  - `updated_at`
- Indexes:
  - index on `word`
  - index on `part_of_speech`

#### `user_dictionary_word`
- Created in `2026_03_31_000005_create_user_dictionary_word_table.php`
- Purpose: pivot table for dictionary <-> word relation
- Fields:
  - `user_dictionary_id` -> FK to `user_dictionaries.id`
  - `word_id` -> FK to `words.id`
  - `created_at`
  - `updated_at`
- Constraints:
  - composite primary key on `user_dictionary_id + word_id`
  - index on `word_id`

## Current Product Decisions

### Dictionary Language
- At the moment dictionaries support:
  - `English`
  - `Spanish`
- Dictionary creation validates language against this fixed set

### Part of Speech
- Current supported values:
  - `noun`
  - `verb`
  - `adjective`
  - `adverb`
  - `pronoun`
  - `preposition`
  - `conjunction`
  - `interjection`
  - `stable_expression`

### Word Ownership Model
- Database structure is currently `many-to-many` between dictionaries and words
- Product behavior currently acts closer to "word created inside a dictionary"
- This is a known architectural tension and should be revisited only if reuse across dictionaries becomes a confirmed product feature

## Automatic Translation Flow

### Current Flow
- UI lives in the dictionary show page (`Show` Livewire component)
- User enters source word
- User clicks `Translate`
- Livewire calls `translateAutomatically()`
- `Show` resolves `TranslationServiceInterface`
- `MyMemoryTranslationService` requests MyMemory API
- Result is normalized into suggestions for chips
- User selects one suggestion
- User completes `part of speech` and optional `comment`
- User clicks `Add`
- `addTranslatedWord()` stores the word in `words` and attaches it in `user_dictionary_word`

### Language Mapping
- Current source language mapping:
  - `English` -> `en`
  - `Spanish` -> `es`
- Current target language is fixed:
  - `ru`

### Fallback Behavior
- If translation fails or no acceptable suggestions are returned:
  - UI shows: `Translation is currently unavailable. Please switch to Enter manually.`
  - user is encouraged to use manual mode

### Suggestion Normalization
- Chips are built from normalized translation suggestions
- Current rule:
  - only suggestions containing Cyrillic and no Latin letters are allowed through to the UI
- This is intended to suppress obvious English/Spanish noise from MyMemory
- Additional semantic noise filtering may still be needed later

## Important Implementation Notes
- Dictionary page totals show the total number of words in the dictionary, independent of active filters
- Search, sorting, part-of-speech filter, and pagination are all handled inside `App\Livewire\Dictionaries\Show`
- Dictionary header dropdown data is passed from Livewire into the layout; the layout should not query dictionaries directly
- External API access should continue to go through service abstractions, not be embedded into Livewire components

## Key Files To Read First
- `routes/web.php`
- `app/Livewire/Dictionaries/Index.php`
- `app/Livewire/Dictionaries/Show.php`
- `app/Models/UserDictionary.php`
- `app/Models/Word.php`
- `app/Services/Translation/MyMemoryTranslationService.php`
- `resources/views/livewire/dictionaries/index.blade.php`
- `resources/views/livewire/dictionaries/show.blade.php`
