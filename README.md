# WordKeeper

WordKeeper is a focused vocabulary workspace for language learners.

Users can collect foreign words in personal dictionaries, keep translations and notes together, translate short texts, and return to those words later in web review sessions or through the Telegram bot.

Website: [https://wordkeeper.space/](https://wordkeeper.space/)  
Telegram bot: [https://t.me/WordKeeperBot_bot](https://t.me/WordKeeperBot_bot)

## Tech stack

- Laravel 13
- Blade + Livewire 4
- PostgreSQL
- Telegram Bot API
- LibreTranslate with MyMemory fallback for translation flows

## Current functionality

### Web app

- registration, login, logout, and localized interface in English and Russian;
- personal dictionaries:
  - create, rename, browse, and delete;
  - supported dictionary languages: English, Spanish, German, Italian, Portuguese;
  - search across all available dictionaries;
- word management in owned dictionaries:
  - add words manually;
  - add words with automatic translation suggestions;
  - edit translation, part of speech, and comment;
  - delete with confirmation;
  - search, filter, sort, and paginate;
- translation flow:
  - LibreTranslate as the primary provider;
  - MyMemory as fallback when LibreTranslate is unavailable;
  - multiple translation suggestions;
- text translator:
  - authenticated-only Translator page in the header;
  - translate short texts up to 4500 characters;
  - choose RU / EN / SP / DE / IT / PT direction before sending the request;
- dictionary sharing:
  - send dictionary subscription invitations by email;
  - accept invitations through a secure token flow;
  - show subscribed dictionaries together with owned dictionaries;
  - open subscribed dictionaries in read-only mode;
- ready dictionaries:
  - browse catalog;
  - filter by language, level, and part of speech;
  - open words in read-only mode;
  - copy words into owned dictionaries;
- Remainder practice:
  - manual translation mode;
  - multiple-choice mode;
  - user-specific mistake tracking;
  - owned and subscribed dictionaries can both be used as word sources;
- guest demo mode for prepared dictionaries and Remainder;
- profile statistics;
- About page with project status, aggregate statistics, legal sections, and contact form.

### Telegram bot

- account linking between website and Telegram;
- main menu for authorized users;
- browse available dictionaries in Telegram:
  - owned dictionaries;
  - subscribed dictionaries;
- search saved words across dictionaries available to the user;
- add a word from Telegram to an owned dictionary:
  - choose dictionary;
  - enter word;
  - choose one of the translation suggestions;
  - choose part of speech;
  - save to the selected owned dictionary;
- scheduled random-word Telegram sessions configured from `/tg-bot`;
- 6-session interval review flow configured from `/tg-bot`;
- subscribed dictionaries can be used as source dictionaries in Telegram learning flows, but not as write targets.

## Product direction

The project is intentionally narrow: fewer distractions, a small number of clear flows, and steady polish of the existing learning loop instead of surface-area growth.

Current focus:

- polishing dictionary sharing and subscription flows;
- improving Telegram-based practice;
- expanding prepared dictionary content;
- keeping changes small, reversible, and stable.
