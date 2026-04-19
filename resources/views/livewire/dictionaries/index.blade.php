<main class="dictionaries-main">
    <section class="dictionaries-container dictionaries-intro">
        <div class="dictionaries-intro__copy">
            <h1 class="dictionaries-title">{{ __('dictionaries.index.title') }}</h1>
            <p class="dictionaries-subtitle">{{ __('dictionaries.index.subtitle') }}</p>
        </div>

        @if (! $showCreateForm)
            <button type="button" class="btn btn-primary dictionaries-new-btn" wire:click="openCreateForm">
                <span class="dictionaries-new-btn__plus">+</span>
                <span>{{ __('dictionaries.index.new_dictionary') }}</span>
            </button>
        @endif
    </section>

    @if ($showCreateForm)
        <section class="dictionaries-container dictionaries-create-card" aria-label="{{ __('dictionaries.index.create_form_aria') }}">
            <form class="dictionaries-create-form" wire:submit="createDictionary" wire:key="dictionary-create-form-{{ $formRenderKey }}">
                <div class="dictionaries-field">
                    <label for="dictionary-name" class="dictionaries-label">{{ __('dictionaries.index.fields.name') }}</label>
                    <input
                        id="dictionary-name"
                        type="text"
                        class="dictionaries-input"
                        placeholder="{{ __('dictionaries.index.placeholders.name') }}"
                        wire:model.defer="name"
                    >
                    @error('name')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-field">
                    <label for="dictionary-language" class="dictionaries-label">{{ __('dictionaries.index.fields.language') }}</label>
                    <select
                        id="dictionary-language"
                        class="dictionaries-input"
                        wire:model.defer="language"
                    >
                        <option value="" disabled>{{ __('dictionaries.index.language_prompt') }}</option>
                        <option value="English">{{ __('dictionaries.index.languages.english') }}</option>
                        <option value="Spanish">{{ __('dictionaries.index.languages.spanish') }}</option>
                    </select>
                    @error('language')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-create-actions">
                    <button type="submit" class="btn btn-primary dictionaries-action-btn">
                        {{ __('dictionaries.index.actions.create') }}
                    </button>
                    <button type="button" class="btn btn-secondary dictionaries-action-btn" wire:click="cancelCreate">
                        {{ __('dictionaries.index.actions.cancel') }}
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="dictionaries-container dictionaries-list" aria-label="{{ __('dictionaries.index.title') }}">
        @forelse ($dictionaries as $dictionary)
            @php
                $languageKey = $dictionary->language !== null ? 'dictionaries.index.languages.' . strtolower($dictionary->language) : 'dictionaries.index.languages.not_specified';
            @endphp
            <article class="dictionary-card" wire:key="dictionary-{{ $dictionary->id }}">
                <div class="dictionary-card__content">
                    @if ($editingDictionaryId === $dictionary->id)
                        <div class="dictionary-card__edit-form">
                            <div class="dictionary-card__edit-field">
                                <input
                                    type="text"
                                    class="dictionary-card__edit-input"
                                    wire:model.defer="editingDictionaryName"
                                    aria-label="{{ __('dictionaries.index.edit.field_aria', ['name' => $dictionary->name]) }}"
                                    wire:keydown.enter.prevent="updateEditingDictionaryName"
                                    wire:keydown.escape.prevent="cancelEditingDictionary"
                                >

                                @error('editingDictionaryName')
                                    <p class="dictionaries-error dictionary-card__edit-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionary-card__edit-actions">
                                <button
                                    type="button"
                                    class="dictionary-card__edit-submit"
                                    wire:click="updateEditingDictionaryName"
                                >
                                    {{ __('dictionaries.index.edit.accept') }}
                                </button>

                                <button
                                    type="button"
                                    class="dictionary-card__edit-cancel"
                                    wire:click="cancelEditingDictionary"
                                >
                                    {{ __('dictionaries.index.edit.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <h2 class="dictionary-card__title">
                            <a href="{{ route('dictionaries.show', $dictionary) }}">{{ $dictionary->name }}</a>
                        </h2>
                    @endif

                    <p class="dictionary-card__meta">
                        {{ __($languageKey) }}
                        <span class="dictionary-card__dot">&middot;</span>
                        {{ trans_choice('dictionaries.index.words_count', $dictionary->words_count ?? 0, ['count' => $dictionary->words_count ?? 0]) }}
                        <span class="dictionary-card__dot">&middot;</span>
                        {{ __('dictionaries.index.meta.created', ['date' => $dictionary->created_at?->translatedFormat('Y-m-d')]) }}
                    </p>
                </div>

                <div class="dictionary-card__actions">
                    <button
                        type="button"
                        class="dictionary-card__edit"
                        wire:click="startEditingDictionary({{ $dictionary->id }})"
                        aria-label="{{ __('dictionaries.index.edit.aria', ['name' => $dictionary->name]) }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m4 20 4.25-1.06a2 2 0 0 0 .9-.52L19 8.57a2.12 2.12 0 0 0-3-3L6.15 15.42a2 2 0 0 0-.52.9L4 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="m14.5 7.5 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="dictionary-card__delete"
                        wire:click="confirmDeleteDictionary({{ $dictionary->id }})"
                        aria-label="{{ __('dictionaries.index.delete.aria', ['name' => $dictionary->name]) }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </article>
        @empty
            <article class="dictionary-card dictionary-card--empty">
                <h2 class="dictionary-card__title">{{ __('dictionaries.index.empty.title') }}</h2>
                <p class="dictionary-card__meta">{{ __('dictionaries.index.empty.text') }}</p>
            </article>
        @endforelse
    </section>

    @if ($pendingDeleteDictionaryId !== null)
        <div class="dictionary-delete-overlay" wire:click="cancelDeleteDictionary">
            <div class="dictionary-delete-dialog" wire:click.stop>
                <div class="dictionary-delete-modal">
                    <h2 class="dictionary-delete-modal__title">{{ __('dictionaries.index.delete.title') }}</h2>

                    <p class="dictionary-delete-modal__text">
                        {{ __('dictionaries.index.delete.text', ['name' => $pendingDeleteDictionaryLabel]) }}
                    </p>

                    <div class="dictionary-delete-modal__actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            wire:click="cancelDeleteDictionary"
                        >
                            {{ __('dictionaries.index.delete.no') }}
                        </button>

                        <button
                            type="button"
                            class="dictionary-delete-modal__danger-btn"
                            wire:click="deleteConfirmedDictionary"
                        >
                            {{ __('dictionaries.index.delete.yes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</main>
