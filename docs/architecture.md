# Project Architecture

## Summary
- Stack: Laravel 13 + Blade + Livewire 4 + PostgreSQL
- Main domain: authenticated users create personal dictionaries, add words to them, and can launch a remainder game session for repetition
- UI architecture:
  - dictionary pages are implemented as Livewire page components
  - auth/profile/about/remainder settings pages use classic Laravel controllers + Blade views
  - active game play is rendered through an embedded Livewire component inside a Blade page
- Translation integration is isolated behind a service abstraction and is not called directly from Blade or controllers

## Main Layers

### Routing
- `routes/web.php` is the main web entrypoint
- Public route:
  - `/` -> `welcome` view
  - `POST /interface-language` -> stores `ru|en` in session, also updates authenticated user's preferred locale when available, and redirects back
- Authenticated routes:
  - `/dashboard` -> redirects to dictionaries index
  - `/profile` -> `ProfileController`
  - `/about` -> `AboutController`
  - `/remainder` -> `RemainderController@index`
  - `POST /remainder/sessions` -> `RemainderController@store`
  - `GET /remainder/sessions/{gameSession}` -> `RemainderController@showSession`
  - `/dictionaries` -> `App\Livewire\Dictionaries\Index`
  - `/dictionaries/{dictionary}` -> `App\Livewire\Dictionaries\Show`

### Controllers
- `App\Http\Controllers\ProfileController`
  - edits profile
  - delegates remainder statistics aggregation to `RemainderStatisticsService`
  - updates profile
  - deletes account
- `App\Http\Controllers\AboutController`
  - renders the authenticated About page
  - delegates aggregate site-wide About statistics to `GlobalStatisticsService`
- `App\Http\Controllers\RemainderController`
  - renders remainder settings page
  - starts manual game sessions
  - renders the game session page shell
- Auth controllers are the standard Breeze-style controllers under `app/Http/Controllers/Auth`
- Dictionaries are not handled by traditional controllers; they are handled by Livewire page components
- Locale switching is currently handled by a small route closure plus web middleware, not by a dedicated controller

### Middleware
- `App\Http\Middleware\SetLocale`
  - resolves locale priority in this order:
    - authenticated user's `preferred_locale`
    - session `ui_locale`
    - application default locale
  - validates the resolved locale against configured supported locales
  - synchronizes the resolved locale back into session
  - calls `app()->setLocale(...)` for each web request

### Livewire Components
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
- `App\Livewire\Remainder\Show`
  - drives one active game session on the remainder game page
  - renders either the current question, immediate feedback, or the final result summary
  - delegates answer checking and session transitions to `GameEngineService`

### Views and Layouts
- Shared dictionaries layout: `resources/views/layouts/dictionaries.blade.php`
- Shared profile-style authenticated layout: `resources/views/layouts/profile.blade.php`
- Dictionary pages:
  - `resources/views/livewire/dictionaries/index.blade.php`
  - `resources/views/livewire/dictionaries/show.blade.php`
- Informational pages:
  - `resources/views/about.blade.php`
  - `resources/views/remainder.blade.php`
  - `resources/views/remainder-show.blade.php`
- Reusable shared components:
  - `resources/views/components/language-switcher.blade.php`
  - `resources/views/components/site-footer.blade.php`
- Translation files:
  - `lang/en/common.php`
  - `lang/ru/common.php`
  - `lang/en/about.php`
  - `lang/ru/about.php`
- Remainder game UI:
  - `resources/views/livewire/remainder/show.blade.php`
- Key styling:
  - `public/css/dictionaries.css`
  - `public/css/dictionary-show.css`
  - `public/css/profile.css`
  - `public/css/footer.css`
  - `public/css/about.css`
  - `public/css/remainder.css`
  - `public/css/remainder-game.css`
- Important design choice:
  - shared layouts do not query dictionaries directly
  - data needed by authenticated layouts is passed from controllers/Livewire
  - the game page keeps the existing profile layout and embeds a Livewire component inside a Blade page instead of turning the whole authenticated layout into a Livewire layout

### Services
- Translation integration lives under `app/Services/Translation`
- Current translation abstraction:
  - `TranslationServiceInterface`
  - `MyMemoryTranslationService`
  - `TranslationResult`
  - `TranslationSuggestion`
- Interface text localization uses standard Laravel lang files plus application locale set by middleware
- Profile read-model services live under `app/Services/Profile`
  - `RemainderStatisticsService`
    - aggregates finished game sessions for the authenticated user's profile page
    - computes preferred mode, preferred direction, totals, and answer accuracy
- About read-model services live under `app/Services/About`
  - `GlobalStatisticsService`
    - aggregates site-wide counts for dictionaries, word entries, and game sessions
    - computes overall answer accuracy across all game sessions
- Remainder game services live under `app/Services/Remainder`
  - `PrepareGameService`
    - validates dictionary ownership at the domain layer
    - collects words using the selected configuration
    - removes duplicates
    - creates the snapshot session and session items
  - `ChoiceOptionsBuilder`
    - builds unique multiple choice options from the full filtered answer pool used to prepare the session
    - persists up to 6 shuffled options per question
  - `GameEngineService`
    - finds the current unanswered item
    - checks manual answers and selected choice answers
    - updates progress counters and finished status
    - produces final result summaries

## Domain Model

### User
- Model: `App\Models\User`
- Important fields:
  - `preferred_locale` nullable
- Relationships:
  - `hasMany(UserDictionary::class)` via `dictionaries()`
  - `hasMany(GameSession::class)` via `gameSessions()`

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

### GameSession
- Model: `App\Models\GameSession`
- Purpose: stores one immutable snapshot of a started game
- Core fields:
  - `user_id`
  - `mode`
  - `direction`
  - `total_words`
  - `correct_answers`
  - `status`
  - `started_at`
  - `finished_at`
  - `config_snapshot`
- Relationships:
  - `belongsTo(User::class)`
  - `hasMany(GameSessionItem::class)->orderBy('order_index')`

### GameSessionItem
- Model: `App\Models\GameSessionItem`
- Purpose: stores one concrete prompt/answer step inside a snapshot
- Core fields:
  - `game_session_id`
  - `word_id`
  - `order_index`
  - `prompt_text`
  - `part_of_speech_snapshot`
  - `correct_answer`
  - `options_json`
  - `user_answer`
  - `is_correct`
  - `answered_at`
- Relationships:
  - `belongsTo(GameSession::class)`
  - `belongsTo(Word::class)`

## Database Structure

### Core Tables

#### `users`
- Standard Laravel users table
- Important fields:
  - `id`
  - `name`
  - `email`
  - `preferred_locale` nullable
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

#### `game_sessions`
- Created in `2026_04_10_000008_create_game_sessions_table.php`
- Purpose: stores one started game snapshot
- Fields:
  - `id`
  - `user_id` -> FK to `users.id`
  - `mode`
  - `direction`
  - `total_words`
  - `correct_answers`
  - `status`
  - `started_at`
  - `finished_at` nullable
  - `config_snapshot` jsonb
  - `created_at`
  - `updated_at`

#### `game_session_items`
- Created in `2026_04_10_000009_create_game_session_items_table.php`
- Purpose: stores the concrete ordered steps for one game snapshot
- Fields:
  - `id`
  - `game_session_id` -> FK to `game_sessions.id`
  - `word_id` -> FK to `words.id`
  - `order_index`
  - `prompt_text`
  - `part_of_speech_snapshot` nullable
  - `correct_answer`
  - `options_json` nullable jsonb
  - `user_answer` nullable
  - `is_correct` nullable
  - `answered_at` nullable
  - `created_at`
  - `updated_at`

## Current Product Decisions

### Interface Locale
- Currently supported interface locales:
  - `ru`
  - `en`
- Guests store the chosen locale in session under `ui_locale`
- Authenticated users can persist locale in `users.preferred_locale` from the profile page or header switcher
- Locale resolution priority:
  - authenticated user's `preferred_locale`
  - session `ui_locale`
  - `config('app.locale')`
- When an authenticated user changes locale via the header switcher, both session and `preferred_locale` are updated

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
  - `cardinal`
  - `preposition`
  - `conjunction`
  - `interjection`
  - `stable_expression`

### Word Ownership Model
- Database structure is currently `many-to-many` between dictionaries and words
- Product behavior currently acts closer to "word created inside a dictionary"
- This is a known architectural tension and should be revisited only if reuse across dictionaries becomes a confirmed product feature
- Current deletion behavior follows the product assumption above:
  - deleting a dictionary also deletes all words attached to that dictionary
  - this is intentional under the current product model, where words are effectively treated as belonging to one dictionary at creation time

### Remainder Game Modes
- Current implemented modes:
  - `manual`
  - `choice`
- Current direction values:
  - `foreign_to_ru`
  - `ru_to_foreign`
- Current session statuses:
  - `active`
  - `finished`

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

## Remainder Game Flow
- Settings page (`/remainder`) uses Blade + Alpine for configuration UI
- Start action posts configuration to `RemainderController@store`
- `StartGameRequest` validates request shape
- `PrepareGameService`:
  - verifies dictionary ownership
  - filters available words by selected dictionaries and parts of speech
  - deduplicates words across many-to-many dictionary selection
  - randomizes order
  - creates `GameSession`
  - creates `GameSessionItem` snapshot rows, including `part_of_speech_snapshot`
- if mode is `choice`, `ChoiceOptionsBuilder` also precomputes `options_json` for every session item from the full filtered answer pool, while `words_count` still controls only the number of rounds
- Game page (`/remainder/sessions/{gameSession}`) renders a Blade shell with embedded `App\Livewire\Remainder\Show`
- `GameEngineService` validates and checks each answer, updates counters, and finishes the session after the last item
- choice-mode warnings about incomplete option sets are stored in `config_snapshot['warnings']` and shown on the game screen
- Result screen is rendered by the same Livewire component when the session status becomes `finished`

## Important Implementation Notes
- Dictionary page totals show the total number of words in the dictionary, independent of active filters
- About page global word totals are counted as total dictionary-word pivot entries, not as unique `words` rows
- Search, sorting, part-of-speech filter, and pagination are all handled inside `App\Livewire\Dictionaries\Show`
- Dictionary header dropdown data is passed from Livewire/controllers into the layout; the layout should not query dictionaries directly
- External API access should continue to go through service abstractions, not be embedded into Livewire components
- Remainder game sessions always use snapshot semantics: once a game is created, item order and answers are read from `game_session_items`, not recalculated from live dictionary data
- part of speech displayed on the game screen is also read from `game_session_items.part_of_speech_snapshot`, not from live `words` rows
- multiple choice distractors are built from the full filtered answer pool captured during preparation, never from live dictionary queries during play

## Key Files To Read First
- `routes/web.php`
- `app/Http/Controllers/RemainderController.php`
- `app/Livewire/Remainder/Show.php`
- `app/Services/Remainder/PrepareGameService.php`
- `app/Services/Remainder/ChoiceOptionsBuilder.php`
- `app/Services/Remainder/GameEngineService.php`
- `app/Models/GameSession.php`
- `app/Models/GameSessionItem.php`
- `resources/views/remainder.blade.php`
- `resources/views/remainder-show.blade.php`
- `resources/views/livewire/remainder/show.blade.php`
