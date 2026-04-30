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
- `routes/console.php` contains scheduled console tasks
- Public route:
  - `/` -> `welcome` view
  - `POST /interface-language` -> stores `ru|en` in session, also updates authenticated user's preferred locale when available, and redirects back
  - `POST /telegram/webhook/{secret}` -> `TelegramWebhookController`, accepts Telegram bot updates through a secretized webhook URL
  - `/ready-dictionaries` -> `ReadyDictionariesController@index`, also used as the guest Prepared dictionaries demo entry page
  - `/ready-dictionaries/{readyDictionary}` -> `App\Livewire\ReadyDictionaries\Show`, readable by guests as part of demo mode
  - `/remainder` -> `RemainderController@index`, available to guests as demo Remainder using ready dictionaries only
  - `POST /remainder/sessions` -> `RemainderController@store`, creates either authenticated user sessions or guest demo sessions
  - `GET /remainder/sessions/{gameSession}` -> `RemainderController@showSession`, allows owners to open user sessions and allows guest demo sessions only via signed URLs bound to the current browser session
  - `/about` -> `AboutController`, readable by guests with demo banner and guest header
- Authenticated routes:
  - `/dashboard` -> redirects to dictionaries index
  - `/profile` -> `ProfileController`
  - `GET /tg-bot` -> `TgBotController@index`
  - `PUT /tg-bot` -> `TgBotController@update`
  - `POST /about/contact` -> `AboutContactController@store`
  - `/dictionaries` -> `App\Livewire\Dictionaries\Index`
  - `/dictionaries/{dictionary}` -> `App\Livewire\Dictionaries\Show`

### Controllers
- `App\Http\Controllers\ProfileController`
  - edits profile
  - delegates remainder statistics aggregation to `RemainderStatisticsService`
  - updates profile
  - deletes account
  - persists user's preferred locale and optional Telegram login
- `App\Http\Controllers\AboutController`
  - renders the About page for authenticated users and guests
  - delegates aggregate site-wide About statistics to `GlobalStatisticsService`
- `App\Http\Controllers\AboutContactController`
  - handles authenticated About contact form submissions
  - validates input through `StoreAboutContactRequest`
  - enqueues email delivery through a queued job instead of sending synchronously in the request cycle
  - persists delivery status in `about_contact_messages`
- `App\Http\Controllers\RemainderController`
  - renders remainder settings page for authenticated users and guests
  - starts user game sessions or guest demo sessions
  - renders the game session page shell with owner/demo access checks
  - signs guest demo session URLs and binds them to the current session/browser context
- `App\Http\Controllers\ReadyDictionariesController`
  - renders the Prepared dictionaries catalog page for authenticated users and guests
  - serves as the first guest demo entry point
  - delegates ready dictionary catalog queries and filter normalization to `ReadyDictionaryCatalogService`
- `App\Http\Controllers\TgBotController`
  - renders the authenticated `TG bot` settings page
  - loads personal dictionaries, prepared dictionaries, timezone options, and saved Telegram configuration
  - blocks settings persistence until the user is actually linked to the bot through `users.tg_chat_id`
  - delegates settings persistence to `SaveTelegramSettingsService`
  - reuses shared authenticated header/footer navigation through `HeaderNavigationService`
- `App\Http\Controllers\TelegramWebhookController`
  - accepts Telegram webhook requests on a public endpoint
  - validates the URL secret against `config('services.telegram.webhook_secret')`
  - delegates both message updates and callback query updates to `TelegramUpdateHandler`
  - always returns `200`, even if update handling raised an internal exception
- Header dropdown data is assembled through `HeaderNavigationService` so shared layouts receive personal dictionaries and ready dictionaries without querying from Blade
- Auth controllers are the standard Breeze-style controllers under `app/Http/Controllers/Auth`
- Dictionaries are not handled by traditional controllers; they are handled by Livewire page components
- Locale switching is currently handled by a small route closure plus web middleware, not by a dedicated controller
- About contact submissions are protected by a dedicated `about-contact` rate limiter
- Guest Remainder demo session creation is throttled inside `RemainderController` per current browser session

### Middleware
- `App\Http\Middleware\SetLocale`
  - resolves locale priority in this order:
    - authenticated user's `preferred_locale`
    - session `ui_locale`
    - application default locale
  - validates the resolved locale against configured supported locales
  - synchronizes the resolved locale back into session
  - calls `app()->setLocale(...)` for each web request
- Telegram webhook access is protected by an explicit secret in the webhook URL, not by session or token-based web auth
- `telegram/webhook/*` is explicitly excluded from Laravel CSRF validation because Telegram cannot send a browser CSRF token

### Livewire Components
- `App\Livewire\Dictionaries\Index`
  - lists current user dictionaries
  - creates dictionaries
  - deletes dictionaries with confirmation modal state
  - passes `headerDictionaries` into the shared dictionaries layout
- `App\Livewire\Dictionaries\Show`
  - shows a single dictionary
  - lists words with pagination
  - receives each word's `remainder_had_mistake` flag for Remainder error markers
  - supports search, sorting, and part-of-speech filtering
  - supports manual word creation
  - supports automatic translation flow
  - deletes words with confirmation modal state
  - passes `headerDictionaries` into the shared dictionaries layout
- `App\Livewire\ReadyDictionaries\Show`
  - shows one developer-managed ready dictionary
  - lists ready dictionary words with pagination
  - supports read-only search, sorting, and part-of-speech filtering
  - allows guests to browse ready dictionary words as demo content
  - does not expose word creation, editing, or deletion actions
  - copying ready words into personal dictionaries remains available only to authenticated users
- `App\Livewire\Remainder\Show`
  - drives one active game session on the remainder game page
  - allows demo sessions while preserving owner checks for user sessions
  - renders either the current question, immediate feedback, or the final result summary
  - delegates answer checking and session transitions to `GameEngineService`
  - allows authenticated users to copy incorrect prepared-dictionary result words into personal dictionaries
- `App\Livewire\TgBot\IntervalReviewConfigurator`
  - renders the first UI-only interval review configurator inside `/tg-bot`
  - filters personal and prepared dictionaries by selected language
  - opens a modal dictionary picker with search, part-of-speech filtering, pagination, and bulk selection
  - keeps the temporary selected words state in Livewire without persisting an interval plan yet
  - builds a preview of the 6-session interval schedule through `TelegramIntervalReviewSchedulePreviewService`

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
  - `resources/views/tg-bot.blade.php`
- Additional TG bot Livewire view:
  - `resources/views/livewire/tg-bot/interval-review-configurator.blade.php`
- Reusable shared components:
  - `resources/views/components/language-switcher.blade.php`
  - `resources/views/components/site-footer.blade.php`
  - `resources/views/components/demo-banner.blade.php`
  - `resources/views/components/demo-sticky-cta.blade.php`
  - `resources/views/components/demo-result-cta.blade.php`
  - demo components keep guest conversion UI reusable across Prepared dictionaries and Remainder pages
- Translation files:
  - `lang/en/common.php`
  - `lang/ru/common.php`
  - `lang/en/about.php`
  - `lang/ru/about.php`
  - profile and validation translations also define the optional Telegram login field
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
  - data needed by authenticated layouts is passed from controllers/Livewire through `HeaderNavigationService`
  - the game page keeps the existing profile layout and embeds a Livewire component inside a Blade page instead of turning the whole authenticated layout into a Livewire layout

### Services
- Translation integration lives under `app/Services/Translation`
- Current translation abstraction:
  - `TranslationServiceInterface`
  - `MyMemoryTranslationService`
  - `TranslationResult`
  - `TranslationSuggestion`
- Interface text localization uses standard Laravel lang files plus application locale set by middleware
- About contact delivery currently uses a dedicated delivery abstraction over NotiSend Email API:
  - `StoreAboutContactRequest`
  - `App\Models\AboutContactMessage`
  - `App\Jobs\SendAboutContactMessageJob`
  - `App\Services\AboutContact\AboutContactDeliveryServiceInterface`
  - `App\Services\AboutContact\NotiSendAboutContactDeliveryService`
  - `App\Mail\AboutContactMessage`
  - the controller stores a pending message and pushes delivery to the queue
  - the queued job sends the contact message to NotiSend `POST /v1/email/messages`
  - delivery uses the primary NotiSend API URL and can fall back to the reserve API URL
  - delivery failures are persisted with both a safe normalized error code and the raw exception message for diagnostics
- Password reset delivery uses a queued custom notification over the same NotiSend Email API:
  - `App\Notifications\Auth\ResetPasswordViaNotiSend`
  - `App\Notifications\Channels\NotiSendMailChannel`
  - `App\Notifications\Messages\NotiSendMessage`
  - `App\Services\NotiSend\NotiSendEmailApiClient`
  - the password broker and reset token flow remain standard Laravel
  - only the delivery channel is replaced, so the email is queued and sent by the worker through NotiSend `POST /v1/email/messages`
- Reset-password mail observability is stored in `password_reset_mail_deliveries`:
  - `User::sendPasswordResetNotification()` creates a `pending` row before queue dispatch
  - `NotiSendMailChannel` marks the row as `sent` or `failed` after API delivery
  - dispatch failures are normalized to `dispatch_failed`
  - provider/API failures store both a normalized `delivery_error` code and the raw `delivery_error_message`
- Telegram bot integration lives under `app/Services/Telegram`
  - `TelegramBotService`
    - wraps Telegram Bot HTTP API calls
    - sends text messages, answers callback queries, clears inline keyboards, edits Telegram message text, and sets the webhook URL
    - retries temporary Telegram API failures (`429`, `5xx`, and connection problems) with a short bounded backoff
    - emits structured logs for retry attempts and terminal transport failures
  - `TelegramUpdateHandler`
    - handles `/start`, `/login`, Telegram-side logout, and email/password linking against existing site users
    - serves the authenticated Telegram dictionary browsing flow (`Словари`)
    - handles callback queries for scheduled Telegram runs (`РќР°С‡Р°С‚СЊ` / `РћС‚РјРµРЅР°`)
    - deduplicates incoming webhook updates before business processing through `TelegramProcessedUpdateService`
    - updates `users.tg_chat_id`, `users.tg_login`, and `users.tg_linked_at` on successful link
    - never persists or logs the submitted password
  - `TelegramDictionaryCallbackData`
    - centralizes callback payload generation and parsing for Telegram dictionary browsing
  - `TelegramDictionaryMenuService`
    - renders the authenticated user's dictionary list in Telegram
    - returns an empty-state message with a link to the site when the user has no dictionaries yet
  - `TelegramDictionaryViewService`
    - renders one user dictionary in Telegram with 20 words per page
    - formats only existing word attributes (`word`, `part of speech`, `translation`, `comment`)
    - builds compact pagination controls and the `К словарям` back navigation
  - `TelegramIntervalReviewSchedulePreviewService`
    - calculates the UI preview schedule for the future interval review mode
    - returns 6 local datetimes based on the selected start time and the shared Telegram timezone
  - `TelegramProcessedUpdateService`
    - stores processed Telegram `update_id` / `callback_query_id` pairs in the database
    - prevents duplicate webhook delivery from re-running start, cancel, and answer side effects
    - keeps failed update attempts visible for diagnostics
  - `TelegramAuthStateStore`
    - stores the temporary login dialog state in cache for 10 minutes
    - keeps the first Telegram auth slice simple without a full state machine subsystem
  - `TelegramGameConfigFactory`
    - maps one configured Telegram random-word session into the shared `GameSessionConfigData`
    - passes per-session `words_count` from `/tg-bot` into the shared game core
  - `TelegramScheduledSessionLocator`
    - finds active due Telegram sessions by comparing `telegram_settings.timezone` + `send_time` against the current UTC minute
    - only considers bot-connected users (`users.tg_chat_id is not null`)
  - `CreateTelegramGameRunService`
    - prepares Telegram run items through the shared Remainder core
    - stores runtime state in Telegram-specific tables instead of reusing web `game_sessions`
  - `TelegramGameRunNotifier`
    - sends the intro message with inline buttons `РќР°С‡Р°С‚СЊ` / `РћС‚РјРµРЅР°`
    - moves the run to `awaiting_start` and stores the Telegram intro message id
    - initializes `last_interaction_at` after successful intro delivery
  - `TelegramGameRunCallbackData`
    - centralizes callback payload generation and parsing for Telegram scheduled runs
  - `TelegramGameRunMonitorService`
    - tracks Telegram run diagnostics and recent activity
    - updates `last_interaction_at` after successful intro, start, answer, and finish steps
    - records runtime failures in `last_error_code`, `last_error_message`, and `last_error_at`
    - marks stale runs as `expired` or `abandoned`
  - `TelegramGameResultFinalizer`
    - finalizes a completed Telegram run
    - persists `correct_answers` and `incorrect_answers`
    - builds the final Telegram summary message with incorrect answers
    - delegates personal-word mistake flag syncing to `RemainderMistakeFlagSyncService`
  - `SaveTelegramSettingsService`
    - creates or updates one `telegram_settings` row per user
    - recreates the configured daily Telegram random-word sessions in a transaction
    - synchronizes selected parts of speech, personal dictionaries, and prepared dictionaries per session
- Profile read-model services live under `app/Services/Profile`
  - `RemainderStatisticsService`
    - aggregates finished game sessions for the authenticated user's profile page
    - also aggregates finished Telegram game runs from `telegram_game_runs`
    - computes preferred mode, preferred direction, totals, and answer accuracy
- About read-model services live under `app/Services/About`
  - `GlobalStatisticsService`
    - aggregates site-wide counts for dictionaries, word entries, and game sessions
    - also aggregates finished Telegram game runs into the global sessions count and answer accuracy
    - computes overall answer accuracy across all game sessions
- Ready dictionary read-model services live under `app/Services/ReadyDictionaries`
  - `ReadyDictionaryCatalogService`
    - returns developer-managed prepared dictionaries for authenticated and guest catalog pages
    - supports backend filters for language, level, and part of speech
    - normalizes unsupported filters so query parameters do not break the page
- Navigation read-model services live under `app/Services/Navigation`
  - `HeaderNavigationService`
    - returns the authenticated user's personal dictionaries for the `My Dictionaries` dropdown
    - returns all ready dictionaries for the Prepared dictionaries dropdown
    - feeds the shared authenticated navigation used by Profile, Dictionaries, Remainder, Ready dictionaries, and TG bot
    - keeps shared layouts free from direct database queries
- Dictionary write helpers live under `app/Services/Dictionaries`
  - `CopyWordToUserDictionaryService`
    - creates a fresh `words` row from a prepared or session snapshot payload
    - attaches the created word to the selected personal dictionary
    - keeps transfer logic reusable between Prepared dictionaries and the Remainder result screen
- Catalogs under `app/Support`
  - `PartOfSpeechCatalog`
    - stores the canonical part-of-speech values and UI labels
  - `LanguageLevelCatalog`
    - stores the canonical language level values from `A0` to `C2` and their labels
- Remainder game services live under `app/Services/Remainder`
  - `Core\GameSessionConfigData`
    - normalizes dictionary ids, ready dictionary ids, parts of speech, and requested words count into one domain config object
    - removes the need for the game core to depend on HTTP request payload shape
  - `Core\GameWordSelectionService`
    - validates dictionary ownership / ready dictionary availability at the domain layer
    - loads candidate words from personal and prepared dictionaries
    - filters by parts of speech
    - applies the `50% previous mistakes` selection rule and top-up behavior
  - `Core\GameItemSnapshotFactory`
    - converts selected domain words into immutable per-question snapshot payloads
    - prepares prompt text, correct answer, and part-of-speech snapshot independently of the delivery channel
  - `Core\GameSessionFactory`
    - persists the immutable `game_sessions` row and its `game_session_items`
    - creates snapshot `words` rows for prepared-dictionary items so live prepared data stays decoupled from played sessions
  - `Core\GameAnswerEvaluator`
    - checks both manual and choice answers
    - performs answer sanitization and case-insensitive comparison logic
  - `Core\RemainderMistakeFlagSyncService`
    - updates `words.remainder_had_mistake` for finished personal-dictionary sessions
    - skips demo sessions and ignores prepared-dictionary snapshots
    - also supports finished Telegram runs and updates only Telegram items with `source_type_snapshot = user`
  - `Core\GameResultSummaryService`
    - produces final result summaries from immutable session items
  - `PrepareGameService`
    - is now the orchestration layer for web/Telegram-compatible session preparation
    - converts validated request config into `GameSessionConfigData`
    - delegates candidate loading and selection to `GameWordSelectionService`
    - delegates immutable item snapshot building to `GameItemSnapshotFactory`
    - delegates persistence to `GameSessionFactory`
  - `ChoiceOptionsBuilder`
    - builds unique multiple choice options from the full filtered answer pool used to prepare the session
    - persists up to 6 shuffled options per question
  - `GameEngineService`
    - remains the public gameplay orchestration layer for the web UI
    - finds the current unanswered item
    - delegates answer checking to `GameAnswerEvaluator`
    - updates progress counters and finished status
    - delegates finished-session mistake-flag syncing to `RemainderMistakeFlagSyncService`
    - delegates final summary generation to `GameResultSummaryService`

## Domain Model

### User
- Model: `App\Models\User`
- Important fields:
  - `preferred_locale` nullable
  - `tg_login` nullable, stored without `@`
  - `tg_chat_id` nullable string, unique
  - `tg_linked_at` nullable timestamp
- Relationships:
  - `hasMany(UserDictionary::class)` via `dictionaries()`
  - `hasMany(GameSession::class)` via `gameSessions()`
  - `hasMany(TelegramGameRun::class)` via `telegramGameRuns()`
  - `hasOne(TelegramSetting::class)` via `telegramSetting()`

### TelegramSetting
- Model: `App\Models\TelegramSetting`
- Purpose:
  - stores user-wide Telegram settings for the `/tg-bot` page
  - currently holds the shared timezone and the active/inactive switch for the `Send random words to Telegram` mode
- Important fields:
  - `user_id` unique foreign key
  - `timezone`
  - `random_words_enabled`
- Relationships:
  - `belongsTo(User::class)`
  - `hasMany(TelegramRandomWordSession::class)` via `randomWordSessions()`
  - `hasMany(TelegramGameRun::class)` via `gameRuns()`

### TelegramRandomWordSession
- Model: `App\Models\TelegramRandomWordSession`
- Purpose:
  - stores one configured Telegram daily session inside the random-word mode
  - each user can configure from 1 to 5 rows
- Important fields:
  - `telegram_setting_id`
  - `position`
  - `send_time`
  - `translation_direction`
  - `words_count`
- Relationships:
  - `belongsTo(TelegramSetting::class)`
  - `belongsToMany(UserDictionary::class)` via `telegram_random_word_session_user_dictionary`
  - `belongsToMany(ReadyDictionary::class)` via `telegram_random_word_session_ready_dictionary`
  - `hasMany(TelegramRandomWordSessionPartOfSpeech::class)` via `partsOfSpeech()`
  - `hasMany(TelegramGameRun::class)` via `gameRuns()`

### TelegramRandomWordSessionPartOfSpeech
- Model: `App\Models\TelegramRandomWordSessionPartOfSpeech`
- Purpose:
  - stores selected parts of speech for one configured Telegram daily session
  - an empty set means the session should later use all parts of speech

### TelegramProcessedUpdate
- Model: `App\Models\TelegramProcessedUpdate`
- Purpose:
  - stores one processed or in-flight Telegram webhook update
  - protects the Telegram runtime from duplicate `update_id` and `callback_query_id` deliveries
- Important fields:
  - `telegram_update_id` nullable unique
  - `callback_query_id` nullable unique
  - `chat_id` nullable
  - `update_type`
  - `status`
  - `attempts`
  - `last_error_message` nullable
  - `processed_at` nullable

### TelegramGameRun
- Model: `App\Models\TelegramGameRun`
- Purpose:
  - stores one real scheduled Telegram launch created from an active Telegram random-word configuration
  - acts as the runtime aggregate for scheduled, active, finished, failed, and stale Telegram session states
- Important fields:
  - `user_id`
  - `telegram_setting_id`
  - `telegram_random_word_session_id`
  - `mode`
  - `direction`
  - `total_words`
  - `status`
  - `scheduled_for`
  - `intro_message_sent_at`
  - `intro_message_id`
  - `started_at`
  - `finished_at`
  - `cancelled_at`
  - `last_interaction_at`
  - `last_error_code`
  - `last_error_message`
  - `last_error_at`
  - `config_snapshot`
- Relationships:
  - `belongsTo(User::class)`
  - `belongsTo(TelegramSetting::class)`
  - `belongsTo(TelegramRandomWordSession::class)`
  - `hasMany(TelegramGameRunItem::class)->orderBy('order_index')`

### TelegramGameRunItem
- Model: `App\Models\TelegramGameRunItem`
- Purpose:
  - stores one immutable prompt/answer snapshot step for a Telegram scheduled run
  - uses the same core preparation semantics as web `game_session_items`, but in Telegram-specific storage
- Important fields:
  - `telegram_game_run_id`
  - `word_id`
  - `order_index`
  - `prompt_text`
  - `part_of_speech_snapshot`
  - `correct_answer`
  - `source_type_snapshot`
  - `options_json`
  - `user_answer`
  - `is_correct`
  - `answered_at`

### UserDictionary
- Model: `App\Models\UserDictionary`
- Fillable:
  - `user_id`
  - `name`
  - `language`

## Database Structure Additions
- `telegram_settings`
  - one row per user
  - stores shared Telegram configuration such as timezone and random-word mode status
- `telegram_random_word_sessions`
  - child rows for `telegram_settings`
  - stores up to 5 configured daily sessions per user
  - each row stores its own `send_time`, `translation_direction`, and `words_count`
- `telegram_random_word_session_part_of_speech`
  - stores selected parts of speech per configured Telegram session
- `telegram_random_word_session_user_dictionary`
  - stores selected personal dictionaries per configured Telegram session
- `telegram_random_word_session_ready_dictionary`
  - stores selected prepared dictionaries per configured Telegram session
- `telegram_game_runs`
  - stores real scheduled Telegram launches created by the dispatcher command
  - unique by `telegram_random_word_session_id + scheduled_for` to prevent duplicate launches for one time slot
  - stores runtime diagnostics such as `last_interaction_at` and the latest error fields
- `telegram_game_run_items`
  - stores immutable snapshot items prepared for each Telegram run
- `telegram_processed_updates`
  - stores processed Telegram webhook identifiers for idempotency and duplicate update protection

### Word
- Model: `App\Models\Word`
- Fillable:
  - `word`
  - `part_of_speech`
  - `translation`
  - `comment`
  - `remainder_had_mistake`
- Relationship:
  - `belongsToMany(UserDictionary::class)` via `dictionaries()`

### ReadyDictionary
- Model: `App\Models\ReadyDictionary`
- Purpose: stores developer-managed prepared dictionaries that are not owned by a user and are safe to expose to guests as demo content
- Fillable:
  - `name`
  - `language`
  - `level`
  - `part_of_speech`
  - `comment`
- Relationships:
  - `hasMany(ReadyDictionaryWord::class)` via `words()`

### ReadyDictionaryWord
- Model: `App\Models\ReadyDictionaryWord`
- Purpose: stores words that belong to developer-managed prepared dictionaries
- Fillable:
  - `ready_dictionary_id`
  - `word`
  - `translation`
  - `part_of_speech`
  - `comment`
- Relationships:
  - `belongsTo(ReadyDictionary::class)` via `readyDictionary()`

### GameSession
- Model: `App\Models\GameSession`
- Purpose: stores one immutable snapshot of a started game
- Core fields:
  - `user_id` nullable for guest demo sessions
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
- Behavior:
  - `isDemo()` returns true when `user_id` is null or `config_snapshot['is_demo']` is true

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
  - `source_type_snapshot`
  - `options_json`
  - `user_answer`
  - `is_correct`
  - `answered_at`
- Relationships:
  - `belongsTo(GameSession::class)`
  - `belongsTo(Word::class)`

### AboutContactMessage
- Model: `App\Models\AboutContactMessage`
- Purpose: stores a copy of one submitted About contact form message plus delivery outcome
- Core fields:
  - `contact_email`
  - `subject`
  - `message`
  - `delivery_status`
  - `delivered_at`
  - `delivery_error`
  - `delivery_error_message`

## Database Structure

### Core Tables

#### `users`
- Standard Laravel users table
- Important fields:
  - `id`
  - `name`
  - `email`
  - `preferred_locale` nullable
  - `tg_login` nullable, stored without `@`
  - `tg_chat_id` nullable string, unique
  - `tg_linked_at` nullable timestamp
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
  - `remainder_had_mistake` boolean, defaults to `false`
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

#### `ready_dictionaries`
- Created in `2026_04_21_000015_create_ready_dictionaries_tables.php`
- Purpose: stores developer-managed dictionaries that are visible to authenticated users but are not owned by a user
- Fields:
  - `id`
  - `name`
  - `language`
  - `level` nullable
  - `part_of_speech` nullable
  - `comment` nullable
  - `created_at`
  - `updated_at`
- Constraints and indexes:
  - unique composite key on `name + language`
  - indexes on `language`, `level`, and `part_of_speech`

#### `ready_dictionary_words`
- Created in `2026_04_21_000015_create_ready_dictionaries_tables.php`
- Purpose: stores words attached to developer-managed ready dictionaries
- Fields:
  - `id`
  - `ready_dictionary_id` -> FK to `ready_dictionaries.id`
  - `word`
  - `translation`
  - `part_of_speech` nullable
  - `comment` nullable
  - `created_at`
  - `updated_at`
- Constraints and indexes:
  - `ready_dictionary_id` cascades on delete
  - indexes on `ready_dictionary_id`, `word`, and `part_of_speech`

#### `game_sessions`
- Created in `2026_04_10_000008_create_game_sessions_table.php`
- Extended in `2026_04_22_000019_make_game_sessions_user_id_nullable.php`
- Purpose: stores one started game snapshot
- Fields:
  - `id`
  - `user_id` nullable -> FK to `users.id`
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
  - `source_type_snapshot` nullable string snapshot of the original item source (`user` or `ready`)
  - `options_json` nullable jsonb
  - `user_answer` nullable
  - `is_correct` nullable
  - `answered_at` nullable
  - `created_at`
  - `updated_at`

#### `telegram_game_runs`
- Created in `2026_04_28_000027_create_telegram_game_runs_tables.php`
- Extended in `2026_04_29_000029_add_result_counters_to_telegram_game_runs_table.php`
- Extended in `2026_04_29_000030_add_reliability_fields_to_telegram_runs_and_create_processed_updates_table.php`
- Purpose: stores one scheduled Telegram launch created from a user's active Telegram random-word configuration
- Fields:
  - `id`
  - `user_id` -> FK to `users.id`
  - `telegram_setting_id` -> FK to `telegram_settings.id`
  - `telegram_random_word_session_id` -> FK to `telegram_random_word_sessions.id`
  - `mode`
  - `direction`
  - `total_words`
  - `correct_answers`
  - `incorrect_answers`
  - `status`
  - `scheduled_for`
  - `intro_message_sent_at` nullable
  - `intro_message_id` nullable
  - `started_at` nullable
  - `finished_at` nullable
  - `cancelled_at` nullable
  - `last_interaction_at` nullable
  - `last_error_code` nullable
  - `last_error_message` nullable
  - `last_error_at` nullable
  - `config_snapshot` jsonb
  - `created_at`
  - `updated_at`
- Constraints:
  - unique composite key on `telegram_random_word_session_id + scheduled_for`

#### `telegram_processed_updates`
- Created in `2026_04_29_000030_add_reliability_fields_to_telegram_runs_and_create_processed_updates_table.php`
- Purpose: stores processed Telegram webhook identifiers and minimal diagnostics for idempotent runtime handling
- Fields:
  - `id`
  - `telegram_update_id` nullable unique
  - `callback_query_id` nullable unique
  - `chat_id` nullable
  - `update_type`
  - `status`
  - `attempts`
  - `last_error_message` nullable
  - `processed_at` nullable
  - `created_at`
  - `updated_at`

#### `telegram_game_run_items`
- Created in `2026_04_28_000027_create_telegram_game_runs_tables.php`
- Purpose: stores the concrete immutable steps for one Telegram scheduled run
- Fields:
  - `id`
  - `telegram_game_run_id` -> FK to `telegram_game_runs.id`
  - `word_id` nullable -> FK to `words.id`
  - `order_index`
  - `prompt_text`
  - `part_of_speech_snapshot` nullable
  - `correct_answer`
  - `source_type_snapshot` nullable
  - `options_json` nullable jsonb
  - `user_answer` nullable
  - `is_correct` nullable
  - `answered_at` nullable
  - `created_at`
  - `updated_at`

#### `about_contact_messages`
- Created in `2026_04_17_000014_create_about_contact_messages_table.php`
- Purpose: stores About page contact form submissions and email delivery state
- Fields:
  - `id`
  - `contact_email`
  - `subject`
  - `message`
  - `delivery_status`
  - `delivered_at` nullable
  - `delivery_error` nullable
  - `delivery_error_message` nullable
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
- Ready dictionaries store `language` as required metadata and are filtered independently through `ReadyDictionaryCatalogService`

### Language Level
- Current supported ready dictionary levels:
  - `A0 Beginner`
  - `A1 Elementary`
  - `A2 Pre-intermediate`
  - `B1 Intermediate`
  - `B2 Upper-Intermediate`
  - `C1 Advanced`
  - `C2 Proficiency`
- Level values and labels are centralized in `LanguageLevelCatalog`

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
- Ready dictionary words are stored separately from user words and are not attached to `user_dictionary_word`
- Users cannot create ready dictionaries through the current application UI; they are developer-managed content

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
- Current Telegram scheduled run statuses:
  - `scheduled`
  - `awaiting_start`
  - `in_progress`
  - `cancelled`
  - `finished`
  - `failed`
  - `expired`
  - `abandoned`

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
- Guests can open `/remainder` in demo mode; the UI shows no personal dictionaries and makes ready dictionaries the available source
- Start action posts configuration to `RemainderController@store`
- `StartGameRequest` validates request shape
- guest requests may include only `ready_dictionary_ids`; `dictionary_ids` are rejected
- guest session creation is throttled per browser session to reduce demo abuse and unbounded snapshot creation
- `PrepareGameService`:
  - converts the start payload into `GameSessionConfigData`
  - creates demo sessions with `user_id = null` and `config_snapshot['is_demo'] = true`
  - delegates filtered candidate loading and ownership checks to `GameWordSelectionService`
  - delegates the `50% mistake words` rule and top-up behavior to `GameWordSelectionService`
  - delegates immutable snapshot payload creation to `GameItemSnapshotFactory`
  - delegates database persistence of `GameSession` and `GameSessionItem` rows to `GameSessionFactory`
- Personal dictionary session items store the original `words.id`, so `GameEngineService` updates `words.remainder_had_mistake` for words attached to the current user's dictionaries when a non-demo session finishes
- Demo sessions never update `words.remainder_had_mistake`
- Ready dictionary words are stored in separate `ready_dictionary_words`; when they are selected for a game, `PrepareGameService` copies them into `words` as session snapshot records so the existing `game_session_items.word_id` flow remains stable
- `PrepareGameService` also stores `game_session_items.source_type_snapshot`, so the result screen can still distinguish personal words from prepared-dictionary words after snapshot creation
- Ready dictionary snapshot words are not attached to `user_dictionary_word`, so Remainder mistake flags should ignore them when applying finished-session updates
- if mode is `choice`, `ChoiceOptionsBuilder` also precomputes `options_json` for every session item from the full filtered answer pool, while `words_count` still controls only the number of rounds
- Game page (`/remainder/sessions/{gameSession}`) renders a Blade shell with embedded `App\Livewire\Remainder\Show`
- guest demo session pages are reachable only through temporary signed URLs that were generated during session creation and are also checked against the current session state
- `GameEngineService` validates and checks each answer, updates counters, and finishes the session after the last item
- `GameEngineService` now acts as a thin orchestration layer over reusable core services, so Telegram can later use the same answer and finalization logic without copying the web flow
- choice-mode warnings about incomplete option sets are stored in `config_snapshot['warnings']` and shown on the game screen
- Result screen is rendered by the same Livewire component when the session status becomes `finished`
- On the finished result screen, authenticated users can copy incorrect prepared-dictionary words into a selected personal dictionary; copied words are created as new `words` rows with `remainder_had_mistake = true`

## Telegram Scheduled Session Flow
- `/tg-bot` now contains two expandable Telegram mode blocks:
  - the persisted random-words mode
  - the first UI-only interval review configurator
- The interval review configurator currently does not persist plans yet:
  - it lets the user choose a language
  - browse personal and prepared dictionaries in modal pickers
  - select up to 20 words
  - preview the future 6-session interval schedule
  - preview the first session word set
- After Telegram authorization, the bot exposes a small reply-keyboard main menu:
  - `Словари`
  - `Выход`
- `Словари` opens the authenticated Telegram dictionary browsing flow
- `TelegramDictionaryMenuService` sends:
  - the user's dictionaries as a numbered list (`1. Name — Language`)
  - or an empty-state message with `https://wordkeeper.space` when the user has no dictionaries yet
- Dictionary selection in Telegram uses inline callback buttons with numeric labels (`1`, `2`, `3`, ...)
- `TelegramDictionaryViewService` shows the chosen dictionary page-by-page:
  - 20 words per page
  - each word includes only the available attributes:
    - word
    - part of speech
    - translation
    - comment
- Dictionary pagination uses a compact inline layout:
  - `← Назад`
  - `X/Y`
  - `Вперёд →`
  - `К словарям`
- Dictionary page navigation edits the existing Telegram message instead of spamming the chat with new messages
- Dictionary callbacks always re-check dictionary ownership against the currently linked `users.tg_chat_id`, so a user cannot open another user's dictionary by forging callback data
- Telegram random-word settings are configured on `/tg-bot` and stored in `telegram_settings` plus child `telegram_random_word_sessions`
- Each configured Telegram daily session stores its own `words_count` in the allowed `2..20` range, and `TelegramGameConfigFactory` passes that value into the shared `GameSessionConfigData`
- `routes/console.php` schedules `telegram:dispatch-scheduled-sessions` every minute with `withoutOverlapping()`
- `routes/console.php` also schedules `telegram:cleanup-stale-runs` every 15 minutes with `withoutOverlapping()`
- `TelegramScheduledSessionLocator` finds active due Telegram sessions by comparing each configured timezone + `send_time` against the current UTC minute
- `CreateTelegramGameRunService` maps each due Telegram session into the shared `GameSessionConfigData`, reuses the Remainder core to select words and precompute choice options, and stores the result in:
  - `telegram_game_runs`
  - `telegram_game_run_items`
- `TelegramGameRunNotifier` sends the intro Telegram message:
  - `Пришло время повторить слова. Запланировано к повторению N слов.`
  - inline buttons:
    - `Начать`
    - `Отмена`
- Before a new scheduled run is created for a user, older unfinished Telegram runs with statuses `awaiting_start` or `in_progress` are automatically moved to `expired`
- When such older runs are closed because a newer scheduled session time has arrived, Telegram sends an informational message explaining that the previous unfinished session was closed before the new intro message is sent
- The Telegram webhook receives callback queries from those buttons
- `TelegramUpdateHandler` first claims the incoming webhook through `TelegramProcessedUpdateService`; duplicate `update_id` / `callback_query_id` deliveries are acknowledged and skipped
- `TelegramUpdateHandler` parses callback payloads through `TelegramGameRunCallbackData`
- `TelegramGameRuntimeService` owns the Telegram question/answer loop and reuses `GameAnswerEvaluator` from the shared Remainder core instead of copying answer-check logic
- `TelegramGameRuntimeService` delegates completed-run finalization to `TelegramGameResultFinalizer`
- `TelegramGameQuestionSender` formats one Telegram question message with:
  - progress line (`Вопрос X из N`)
  - prompt text
  - optional part-of-speech label
  - 6 inline answer buttons
- On `Отмена`:
  - the run moves from `awaiting_start` to `cancelled`
  - `cancelled_at` is stored
  - inline buttons are removed from the intro message
- On `Начать`:
  - the run moves from `awaiting_start` to `in_progress`
  - `started_at` is stored
  - inline buttons are removed from the intro message
  - the first unanswered `telegram_game_run_item` is sent immediately
- On `telegram_answer:{runId}:{itemId}:{optionIndex}` callback:
  - the handler verifies run ownership through `users.tg_chat_id`
  - the runtime checks that the run is `in_progress`, the item belongs to that run, the item is still unanswered, the callback points to the current unanswered item, and the selected option index exists
  - the selected option text is evaluated through `GameAnswerEvaluator::evaluateChoiceAnswer()`
  - `telegram_game_run_items.user_answer`, `is_correct`, and `answered_at` are stored
  - the bot sends immediate feedback:
    - `Корректно.`
    - or `Некорректно. Правильный ответ: ...`
  - if there is another unanswered item, the next question is sent immediately
  - if all items are answered:
    - the run stores `correct_answers` and `incorrect_answers`
    - personal words synchronize `words.remainder_had_mistake`
    - Telegram receives a final summary with the incorrect answers list
- Runtime diagnostics:
  - successful intro delivery, start, answer handling, and finalization refresh `telegram_game_runs.last_interaction_at`
  - runtime/send failures store `last_error_code`, `last_error_message`, and `last_error_at`
  - transport retries are limited to recoverable Telegram API failures only
- Duplicate update protection:
  - repeated `update_id` or `callback_query_id` values are persisted in `telegram_processed_updates`
  - duplicate deliveries must not send the same question twice, apply counters twice, or repeat finished transitions
- Stale run cleanup:
  - `awaiting_start` runs older than 12 hours are marked `expired`
  - `in_progress` runs without activity for more than 12 hours are marked `abandoned`
- `/profile` statistics now include finished Telegram runs together with finished web `game_sessions`
- `/about` global statistics now include finished Telegram runs in the total sessions counter and answer accuracy

## Important Implementation Notes
- Dictionary page totals show the total number of words in the dictionary, independent of active filters
- About page global dictionary totals include both user dictionaries and ready dictionaries
- About page global word totals include user dictionary-word pivot entries plus ready dictionary words, not unique `words` rows only
- About contact form submissions are currently available only to authenticated users and are delivered to a fixed recipient email while also being stored in `about_contact_messages`
- About contact form submissions are throttled per authenticated user and no longer wait for SMTP inside the HTTP response cycle
- Search, sorting, part-of-speech filter, and pagination are all handled inside `App\Livewire\Dictionaries\Show`
- Dictionary header dropdown data is passed from Livewire/controllers into the layout; the layout should not query dictionaries directly
- External API access should continue to go through service abstractions, not be embedded into Livewire components
- Remainder game sessions always use snapshot semantics: once a game is created, item order and answers are read from `game_session_items`, not recalculated from live dictionary data
- part of speech displayed on the game screen is also read from `game_session_items.part_of_speech_snapshot`, not from live `words` rows
- multiple choice distractors are built from the full filtered answer pool captured during preparation, never from live dictionary queries during play

## Key Files To Read First
- `routes/web.php`
- `routes/console.php`
- `app/Http/Controllers/RemainderController.php`
- `app/Http/Controllers/TelegramWebhookController.php`
- `app/Livewire/Remainder/Show.php`
- `app/Services/Remainder/PrepareGameService.php`
- `app/Services/Remainder/Core/GameSessionConfigData.php`
- `app/Services/Remainder/Core/GameWordSelectionService.php`
- `app/Services/Remainder/Core/GameItemSnapshotFactory.php`
- `app/Services/Remainder/Core/GameSessionFactory.php`
- `app/Services/Remainder/Core/GameAnswerEvaluator.php`
- `app/Services/Remainder/Core/RemainderMistakeFlagSyncService.php`
- `app/Services/Remainder/Core/GameResultSummaryService.php`
- `app/Services/Remainder/ChoiceOptionsBuilder.php`
- `app/Services/Remainder/GameEngineService.php`
- `app/Services/Telegram/TelegramScheduledSessionLocator.php`
- `app/Services/Telegram/TelegramGameConfigFactory.php`
- `app/Services/Telegram/CreateTelegramGameRunService.php`
- `app/Services/Telegram/TelegramDictionaryCallbackData.php`
- `app/Services/Telegram/TelegramDictionaryMenuService.php`
- `app/Services/Telegram/TelegramDictionaryViewService.php`
- `app/Services/Telegram/TelegramIntervalReviewSchedulePreviewService.php`
- `app/Services/Telegram/TelegramGameRunNotifier.php`
- `app/Services/Telegram/TelegramGameRunCallbackData.php`
- `app/Services/Telegram/TelegramGameResultFinalizer.php`
- `app/Services/Telegram/TelegramUpdateHandler.php`
- `app/Livewire/TgBot/IntervalReviewConfigurator.php`
- `app/Console/Commands/DispatchScheduledTelegramSessionsCommand.php`
- `app/Models/GameSession.php`
- `app/Models/GameSessionItem.php`
- `app/Models/TelegramGameRun.php`
- `app/Models/TelegramGameRunItem.php`
- `resources/views/remainder.blade.php`
- `resources/views/remainder-show.blade.php`
- `resources/views/livewire/remainder/show.blade.php`


