<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        <header class="dictionary-show__header">

            <div class="dictionary-show__title-row">
                <h1 class="dictionary-show__title">{{ $dictionary->name }}</h1>
                @if (! $showCreateForm)
                    <button
                        type="button"
                        class="btn btn-primary dictionaries-new-btn dictionary-show__add-btn"
                        wire:click="openCreateForm"
                    >
                        <span class="dictionaries-new-btn__plus">+</span>
                        <span>Add Word</span>
                    </button>
                @endif
            </div>

            <p class="dictionary-show__subtitle">
                Language <b>{{ $dictionary->language ?? 'not specified' }}</b> &middot; Created on {{ $dictionary->created_at?->format('Y-m-d') ?? 'unknown date' }}
            </p>
        </header>

        @if ($showCreateForm)
            <section
                class="dictionaries-create-card dictionary-show__create-card"
                aria-label="Add word form"
            >
                <form
                    class="dictionaries-create-form dictionary-show__create-form"
                    wire:submit.prevent="addWord"
                >
                    <div class="dictionaries-field">
                        <label for="word-name" class="dictionaries-label">Word</label>
                        <input
                            id="word-name"
                            type="text"
                            class="dictionaries-input"
                            placeholder="e.g., buongiorno"
                            wire:model.defer="word"
                        >
                        @error('word')
                            <p class="dictionaries-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="dictionaries-field">
                        <label for="word-translation" class="dictionaries-label">Translation</label>
                        <input
                            id="word-translation"
                            type="text"
                            class="dictionaries-input"
                            placeholder="e.g., good morning"
                            wire:model.defer="translation"
                        >
                        @error('translation')
                            <p class="dictionaries-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="dictionaries-field dictionary-show__comment-field">
                        <label for="word-comment" class="dictionaries-label">Comment</label>
                        <input
                            id="word-comment"
                            type="text"
                            class="dictionaries-input"
                            placeholder="e.g., formal greeting"
                            wire:model.defer="comment"
                        >
                        @error('comment')
                            <p class="dictionaries-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="dictionaries-create-actions dictionary-show__create-actions">
                        <button type="submit" class="btn btn-primary dictionaries-action-btn">
                            Add
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary dictionaries-action-btn"
                            wire:click="cancelCreate"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <article class="dictionary-show-card" aria-label="Dictionary details">
            <div class="word-list-header">
                <div>
                    <h2 class="dictionary-show-card__title">Word List</h2>
                    <p class="word-list-subtitle">{{ $words->total() }} words in this dictionary</p>
                </div>

                <div class="word-list-controls">
                    <label class="word-list-search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <input type="text" placeholder="Search word or translation..." aria-label="Search words">
                    </label>

                    <select class="word-list-select" aria-label="Sort words">
                        <option>Newest first</option>
                        <option>A-Z</option>
                        <option>Oldest first</option>
                    </select>
                </div>
            </div>

            @if ($words->isEmpty())
                <p class="dictionary-show-list__empty">No words yet. Add your first word using the form above.</p>
            @else
                <div class="word-list-table-wrap">
                    <table class="word-list-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Word</th>
                                <th style="width: 22%;">Translation</th>
                                <th style="width: 30%;">Comment</th>
                                <th style="width: 12%;">Added</th>
                                <th style="width: 8%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($words as $wordItem)
                                <tr>
                                    <td>
                                        <div class="word-list-main">{{ $wordItem->word }}</div>
                                        <div class="word-list-meta">{{ $dictionary->language ?? 'Language not specified' }}</div>
                                    </td>
                                    <td>
                                        <div class="word-list-translation">{{ $wordItem->translation }}</div>
                                    </td>
                                    <td>
                                        <div class="word-list-comment">{{ $wordItem->comment ?: 'No comment' }}</div>
                                    </td>
                                    <td>
                                        <span class="word-list-badge">{{ $wordItem->pivot->created_at?->format('M d') ?? '-' }}</span>
                                    </td>
                                    <td class="word-list-action-cell">
                                        <button
                                            type="button"
                                            class="word-list-delete-btn"
                                            wire:click="deleteWord({{ $wordItem->id }})"
                                            wire:confirm="Delete this word from the system?"
                                            aria-label="Delete word {{ $wordItem->word }}"
                                        >
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="word-list-pagination">
                    <p class="word-list-pagination__info">
                        Showing {{ $words->firstItem() }}-{{ $words->lastItem() }} of {{ $words->total() }} words
                    </p>

                    <div class="word-list-pagination__nav">
                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="previousPage"
                            @disabled($words->onFirstPage())
                        >
                            Prev
                        </button>

                        @for ($page = 1; $page <= $words->lastPage(); $page++)
                            <button
                                type="button"
                                class="word-list-page-btn {{ $words->currentPage() === $page ? 'is-active' : '' }}"
                                wire:click="gotoPage({{ $page }})"
                            >
                                {{ $page }}
                            </button>
                        @endfor

                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="nextPage"
                            @disabled(! $words->hasMorePages())
                        >
                            Next
                        </button>
                    </div>
                </div>
            @endif
        </article>
    </section>
</main>
