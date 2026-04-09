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
| Create a word repetition mode | `in progress` |
| Create a Telegram bot | `planning` |
| Connect site functionality to the Telegram bot | `planning` |
| Create a mode for sending words to the Telegram bot | `planning` |
| Switch to another local translation provider | `planning` |

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
A simple entry point that introduces the product and routes users to authentication.

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
- change password
- delete account
- read an About page describing the project and roadmap

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
- architecture decisions are documented in [`docs/architecture.md`](docs/architecture.md)

## Roadmap direction

The next major step is turning stored vocabulary into active practice, starting with a dedicated repetition mode for words. After that, the product can grow toward Telegram-based workflows and deeper spaced learning scenarios.

---

Built as a personal vocabulary product with a clean Laravel + Livewire architecture and room to grow.
