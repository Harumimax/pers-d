# WordKeeper

> A calm, modern workspace for building your personal foreign-word dictionaries.

WordKeeper is a focused vocabulary app for people who want one neat place to collect words, keep translations close at hand, and organize learning by separate dictionaries instead of scattering vocabulary across notes, chats, and browser tabs.

## Why this project exists

WordKeeper is designed to keep the learning flow simple:

- create personal dictionaries
- add words manually or through assisted translation
- store part of speech and comments
- search, sort, and filter vocabulary inside each dictionary
- keep everything in one clean authenticated workspace

The product is intentionally lightweight, practical, and built around personal use first.

## Current functionality

| Functionality | Status |
| --- | --- |
| Create and manage personal dictionaries | `done` |
| Add words manually with translation, part of speech, and comment | `done` |
| Search, filter, sort, and paginate words inside a dictionary | `done` |
| Automatic translation suggestions during word creation | `done` |
| Delete dictionaries and words with confirmation dialogs | `done` |
| Play Remainder sessions with manual translation input | `done` |
| Play Remainder sessions in multiple choice mode | `done` |
| Store part of speech inside the game session snapshot | `done` |
| Show personal Remainder statistics on the profile page | `done` |
| Switch the interface between Russian and English | `done` |
| Remember a preferred interface language for authenticated users | `done` |
| Localize auth, welcome, and product flows in Russian and English | `done` |
| Add a placeholder contact form on the About page | `done` |
| Create a Telegram bot | `planning` |
| Connect site functionality to the Telegram bot | `planning` |
| Create a mode for sending words to the Telegram bot | `planning` |
| Switch to another local translation provider | `planning` |
| Connect the About page contact form to real email delivery | `planning` |

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
- open a dictionary page
- browse all words in a structured table

### Words
Inside a dictionary, users can:

- add words manually
- use assisted translation mode
- choose part of speech
- save comments
- search by word or translation
- filter by part of speech
- sort results
- paginate large word lists

### Profile and About
Authenticated users can:

- update profile information
- choose and persist a preferred interface language
- change password
- delete account
- read an About page describing the project and roadmap
- view personal Remainder statistics on the profile page
- open a placeholder contact form section on the About page

### Remainder
Authenticated users can:

- configure a repetition session by dictionaries, part of speech, direction, and words count
- play in `manual translation input` mode
- play in `multiple choice` mode
- finish a session and see a result summary with incorrect answers
- keep session questions stable through snapshot-based game items

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
- dictionary and word management are the core of the current product
- game sessions are snapshot-based and do not depend on live dictionary data during play
- architecture decisions are documented in [`docs/architecture.md`](docs/architecture.md)

## Roadmap direction

The current product already covers the main dictionary workflow, a working Remainder game flow, and a bilingual interface with remembered user language preference. The next major steps are mail-backed feedback delivery and later Telegram-based workflows with deeper learning scenarios.

---

Built as a personal vocabulary product with a clean Laravel + Livewire architecture and room to grow.
