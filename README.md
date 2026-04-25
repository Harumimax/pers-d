# WordKeeper

> A calm, modern workspace for building your personal foreign-word dictionaries.

WordKeeper is a focused vocabulary app for people who want one neat place to collect words, keep translations close at hand, and organize learning by separate dictionaries instead of scattering vocabulary across notes, chats, and browser tabs.

## Why this project exists

WordKeeper is designed to keep the learning flow simple:

- create personal dictionaries
- add words manually or through assisted translation
- store part of speech and comments
- search, sort, and filter vocabulary inside each dictionary
- browse developer-managed ready dictionaries
- keep everything in one clean authenticated workspace

The product is intentionally lightweight, practical, and built around personal use first.

## Current functionality

| Functionality | Status |
| --- | --- |
| Create and manage personal dictionaries | `done` |
| Add words manually with translation, part of speech, and comment | `done` |
| Rename personal dictionaries | `done` |
| Edit word translation, part of speech, and comment | `done` |
| Search, filter, sort, and paginate words inside a dictionary | `done` |
| Automatic translation suggestions during word creation | `done` |
| Delete dictionaries and words with confirmation dialogs | `done` |
| Browse ready dictionaries managed by the project | `done` |
| Open a ready dictionary and browse its words read-only | `done` |
| Filter ready dictionaries by language, level, and part of speech | `done` |
| Copy words from prepared dictionaries into personal dictionaries | `done` |
| Seed prepared dictionaries through data migrations | `done` |
| Play Remainder sessions with manual translation input | `done` |
| Play Remainder sessions in multiple choice mode | `done` |
| Use guest demo mode for Prepared dictionaries and Remainder without an account | `done` |
| Store part of speech inside the game session snapshot | `done` |
| Show personal Remainder statistics on the profile page | `done` |
| Switch the interface between Russian and English | `done` |
| Remember a preferred interface language for authenticated users | `done` |
| Localize auth, welcome, and product flows in Russian and English | `done` |
| Send About page contact form messages through the NotiSend API and store delivery status | `done` |
| Copy incorrect prepared-dictionary result words into a personal dictionary after a finished session | `done` |
| Show aggregate site statistics on the About page | `done` |
| Publish privacy and cookie policy sections on the About page | `done` |
| Add Yandex Metrika through environment-based configuration | `done` |
| Show welcome-page use cases and product preview screenshots | `done` |
| Create a Telegram bot | `planning` |
| Connect site functionality to the Telegram bot | `planning` |
| Create a mode for sending words to the Telegram bot | `planning` |
| Switch to another local translation provider | `planning` |
| Make the game interface more varied with alternate progress images and memes | `planning` |

## Product feel

WordKeeper aims to feel:

- minimal, not overloaded
- fast to navigate
- comfortable for everyday vocabulary work
- structured enough to grow into stronger learning workflows later

## Tech stack

- Laravel 13
- Blade
- Livewire 4
- PostgreSQL

Translation suggestions are integrated through a service abstraction, so external translation logic stays outside the UI layer.

## Main app areas

### Welcome page
A localized entry point that introduces the product, lets visitors switch interface language, and routes users to authentication.

### Dictionaries
Authenticated users can:

- create dictionaries
- choose a dictionary language
- rename dictionaries
- open a dictionary page
- browse all words in a structured table

### Words
Inside a dictionary, users can:

- add words manually
- use assisted translation mode
- choose part of speech
- save comments
- edit translation, part of speech, and comments
- search by word or translation
- filter by part of speech
- sort results
- paginate large word lists

### Ready dictionaries
Authenticated users can:

- browse ready dictionaries managed by the project
- filter ready dictionaries by language, level, and part of speech
- open a ready dictionary page
- view ready dictionary words in a read-only table
- copy a ready word into one of their personal dictionaries
- search, filter, sort, and paginate ready dictionary words

Current seeded prepared dictionaries include:

- `100 English words`
- `The most commonly used English verbs`
- `The most commonly used English adjectives`

### Profile and About
Authenticated users can:

- update profile information
- choose and persist a preferred interface language
- change password
- delete account
- read an About page describing the project and roadmap
- view personal Remainder statistics on the profile page
- view aggregate site statistics on the About page
- send a message through the About page contact form
- read privacy and cookie policy sections

The About contact form is queued and delivered through the NotiSend Email API.

### Remainder
Authenticated users can:

- configure a repetition session by dictionaries, part of speech, direction, and words count
- play in `manual translation input` mode
- play in `multiple choice` mode
- finish a session and see a result summary with incorrect answers
- copy incorrect prepared-dictionary result words into a personal dictionary
- keep session questions stable through snapshot-based game items

Guests can also:

- browse Prepared dictionaries in demo mode
- start demo Remainder sessions from prepared dictionaries
- open only the demo sessions created in their current browser session through signed URLs

## Quick start

```bash
git clone https://github.com/Harumimax/pers-d.git
cd pers-d
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

If you use frontend assets locally as part of your workflow:

```bash
npm install
npm run dev
```

## Tests

Run the test suite with:

```bash
php artisan test
```

## Project notes

- authentication is required for the main product flows
- guest demo mode is available for Prepared dictionaries and Remainder
- dictionary and word management are the core of the current product
- game sessions are snapshot-based and do not depend on live dictionary data during play
- About contact form delivery uses the NotiSend Email API through queued jobs
- architecture decisions are documented in [`docs/architecture.md`](docs/architecture.md)

## Roadmap direction

The current product already covers the main dictionary workflow, prepared dictionary browsing, a working Remainder game flow, guest demo mode, API-backed feedback delivery, and a bilingual interface with remembered user language preference. The next major steps are expanding prepared content and later Telegram-based workflows with deeper learning scenarios.

---

Built as a personal vocabulary product with a clean Laravel + Livewire architecture and room to grow.
