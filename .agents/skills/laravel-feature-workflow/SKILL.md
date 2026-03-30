---
name: laravel-feature-workflow
description: Use this when implementing or changing a Laravel feature in this repository. Trigger for migrations, models, Eloquent relations, controllers, services, requests, Blade, Livewire, and tests. Do not use for pure Git-only tasks.
---

You are working in a Laravel + Blade/Livewire + PostgreSQL project.

Follow this workflow exactly:

1. First, restate the task in 2-4 short bullets.
2. Then make a short implementation plan.
3. Before editing, list the files you expect to create or modify.
4. Prefer small, reversible changes.
5. Use standard Laravel conventions:
   - migrations
   - Eloquent relations
   - Form Request validation where useful
   - service classes for external API integration
   - Blade/Livewire for UI
6. Do not add new packages unless the user explicitly asks or there is no reasonable built-in Laravel solution.
7. Do not refactor unrelated code.
8. After changes:
   - summarize what changed
   - show verification commands
   - mention any manual steps
9. When changing schema, remind the user to run migrations.
10. If the task is large, do only the first safe slice and stop.

Project architecture reminders:
- User creates dictionaries
- Dictionary belongs to user
- Word can belong to multiple dictionaries
- Dictionary <-> Word is many-to-many
- Translation logic should live behind a service abstraction, not inside controllers