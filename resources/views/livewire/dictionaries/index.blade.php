<main class="dictionaries-main">
    <section class="dictionaries-container dictionaries-intro">
        <div class="dictionaries-intro__copy">
            <h1 class="dictionaries-title">My Dictionaries</h1>
            <p class="dictionaries-subtitle">Manage your foreign word collections</p>
        </div>

        @if (! $showCreateForm)
            <button type="button" class="btn btn-primary dictionaries-new-btn" wire:click="openCreateForm">
                <span class="dictionaries-new-btn__plus">+</span>
                <span>New Dictionary</span>
            </button>
        @endif
    </section>

    @if ($showCreateForm)
        <section class="dictionaries-container dictionaries-create-card" aria-label="Create dictionary form">
            <form class="dictionaries-create-form" wire:submit="createDictionary">
                <div class="dictionaries-field">
                    <label for="dictionary-name" class="dictionaries-label">Dictionary Name</label>
                    <input
                        id="dictionary-name"
                        type="text"
                        class="dictionaries-input"
                        placeholder="e.g., Italian Basics"
                        wire:model.defer="name"
                    >
                    @error('name')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-field">
                    <label for="dictionary-language" class="dictionaries-label">Language</label>
                    <select
                        id="dictionary-language"
                        class="dictionaries-input"
                        wire:model.defer="language"
                    >
                        <option value="" disabled>Select language</option>
                        <option value="English">English</option>
                        <option value="Spanish">Spanish</option>
                    </select>
                    @error('language')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-create-actions">
                    <button type="submit" class="btn btn-primary dictionaries-action-btn">
                        Создать
                    </button>
                    <button type="button" class="btn btn-secondary dictionaries-action-btn" wire:click="cancelCreate">
                        Отмена
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="dictionaries-container dictionaries-list" aria-label="Dictionaries list">
        @forelse ($dictionaries as $dictionary)
            <article class="dictionary-card" wire:key="dictionary-{{ $dictionary->id }}">
                <div class="dictionary-card__content">
                    <h2 class="dictionary-card__title">
                        <a href="{{ route('dictionaries.show', $dictionary) }}">{{ $dictionary->name }}</a>
                    </h2>

                    <p class="dictionary-card__meta">
                        {{ $dictionary->language ?? 'Language not specified' }}
                        <span class="dictionary-card__dot">·</span>
                        {{ $dictionary->words_count ?? 0 }} words
                        <span class="dictionary-card__dot">·</span>
                        Created {{ $dictionary->created_at?->format('Y-m-d') }}
                    </p>
                </div>

                <button
                    type="button"
                    class="dictionary-card__delete"
                    wire:click="deleteDictionary({{ $dictionary->id }})"
                    wire:confirm="Delete this dictionary?"
                    aria-label="Delete dictionary {{ $dictionary->name }}"
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </article>
        @empty
            <article class="dictionary-card dictionary-card--empty">
                <h2 class="dictionary-card__title">No dictionaries yet</h2>
                <p class="dictionary-card__meta">Create your first dictionary to start organizing words.</p>
            </article>
        @endforelse
    </section>
</main>
