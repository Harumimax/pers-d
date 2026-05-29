<div align="center">

# 📖 WordKeeper

### A calm vocabulary workspace for saving, organizing, and reviewing foreign words.

Save words from real life.  
Keep translations and notes together.  
Practice them later on the web or in Telegram.

<br>

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](#tech-stack)
[![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6?style=for-the-badge)](#tech-stack)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Database-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](#tech-stack)
[![Telegram Bot](https://img.shields.io/badge/Telegram-@WordKeeperBot__bot-26A5E4?style=for-the-badge&logo=telegram&logoColor=white)](#telegram-bot)

<br>

**Website:** https://wordkeeper.space/  
**Telegram bot:** https://t.me/WordKeeperBot_bot

</div>

---

## ✨ What is WordKeeper?

**WordKeeper** is a personal foreign-word dictionary for language learners who want one focused place to collect vocabulary and return to it later.

Instead of keeping words across notes, chats, screenshots, and browser tabs, WordKeeper gives you one structured workspace for:

- creating personal dictionaries;
- saving words, translations, parts of speech, and comments;
- using automatic translation suggestions while adding words;
- practicing vocabulary in web-based review sessions;
- continuing the workflow inside the Telegram bot.

---

## 🧠 Product idea

WordKeeper is built around a simple learning loop:

```text
Find a word → Save it → Organize it → Practice it → Remember it
```

The product stays intentionally narrow: fewer distractions, cleaner navigation, and a small number of flows that are easy to return to every day.

---

## ✅ Current functionality

### Web app

- registration, login, logout, and localized interface in English and Russian;
- personal dictionaries: create, rename, browse, and delete;
- word management inside personal dictionaries:
  - add words manually;
  - edit translation, part of speech, and comment;
  - delete with confirmation;
  - search, filter, sort, and paginate;
- automatic translation suggestions while adding words;
- LibreTranslate as the primary translation provider with MyMemory fallback;
- ready dictionaries managed by the project:
  - browse catalog;
  - filter by language, level, and part of speech;
  - open words in read-only mode;
  - copy words into personal dictionaries;
- Remainder practice sessions:
  - manual translation mode;
  - multiple-choice mode;
  - result summaries and profile statistics;
- guest demo mode for trying prepared dictionaries and Remainder without an account;
- About page with project status, aggregate site statistics, legal sections, and contact form.

### Telegram bot

- account linking and login flow between the site and Telegram;
- main bot menu for authorized users;
- browse personal dictionaries inside Telegram;
- search saved words across personal dictionaries;
- add a new word from Telegram:
  - choose a dictionary;
  - enter a word;
  - pick one of the translation suggestions;
  - choose part of speech;
  - save it into the selected dictionary;
- scheduled random-word Telegram sessions configured from `/tg-bot`;
- 6-session interval review flow configured from `/tg-bot`.

---

## 🛠 Tech stack

- Laravel
- Blade + Livewire
- PostgreSQL
- Telegram Bot API
- LibreTranslate + MyMemory fallback for translation suggestions

---

## Telegram bot

Bot link: https://t.me/WordKeeperBot_bot

The Telegram bot is not a separate product. It extends the same vocabulary workflow and lets users keep working with their dictionaries when they are away from the website.

---

## Status

The current focus is not adding random surface area. It is polishing the existing learning workflow, improving Telegram practice modes, and expanding the prepared dictionary content with small, reversible updates.
