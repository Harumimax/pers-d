@extends('layouts.profile')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\VersionedAsset::url('css/admin.css') }}">
@endpush

@section('content')
@php
    $adminFlash = session('admin_flash');
@endphp
<main class="profile-main admin-main">
    <div
        class="profile-container admin-container"
        x-data="{ userToDelete: null, dictionaryToDelete: null, readyDictionaryToDelete: null }"
    >
        <header class="profile-page-header admin-page-header">
            <div class="admin-page-header__content">
                <p class="admin-page-header__eyebrow">{{ __('admin.title') }}</p>
                <h1 class="profile-title admin-page-header__title">{{ __('admin.title') }}</h1>
                <p class="admin-page-header__description">{{ __('admin.description') }}</p>
            </div>
        </header>

        @if (is_array($adminFlash) && filled($adminFlash['message'] ?? null))
            <div class="admin-flash admin-flash--{{ $adminFlash['type'] ?? 'success' }}" role="status">
                {{ $adminFlash['message'] }}
            </div>
        @endif

        <section class="profile-card admin-card admin-filters-card">
            <div class="admin-section-heading">
                <div>
                    <h2 class="profile-section__title">{{ __('admin.filters.title') }}</h2>
                    <p class="profile-section__description">{{ __('admin.filters.description') }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.index') }}" class="admin-filters-form">
                <label class="profile-field">
                    <span class="profile-label">{{ __('admin.filters.user_email') }}</span>
                    <input
                        type="text"
                        name="user_email"
                        value="{{ $filters['user_email'] }}"
                        class="profile-input"
                        maxlength="255"
                    >
                </label>

                <label class="profile-field">
                    <span class="profile-label">{{ __('admin.filters.user_dictionary_name') }}</span>
                    <input
                        type="text"
                        name="user_dictionary_name"
                        value="{{ $filters['user_dictionary_name'] }}"
                        class="profile-input"
                        maxlength="255"
                    >
                </label>

                <label class="profile-field">
                    <span class="profile-label">{{ __('admin.filters.ready_dictionary_name') }}</span>
                    <input
                        type="text"
                        name="ready_dictionary_name"
                        value="{{ $filters['ready_dictionary_name'] }}"
                        class="profile-input"
                        maxlength="255"
                    >
                </label>

                <div class="admin-filters-actions">
                    <button type="submit" class="btn btn-primary profile-submit-btn">
                        {{ __('admin.filters.apply') }}
                    </button>
                    <a href="{{ route('admin.index') }}" class="btn btn-secondary profile-submit-btn">
                        {{ __('admin.filters.reset') }}
                    </a>
                </div>
            </form>
        </section>

        <section class="profile-card admin-card" id="admin-users-section">
            <div class="admin-section-heading">
                <div>
                    <h2 class="profile-section__title">{{ __('admin.users.title') }}</h2>
                    <p class="profile-section__description">{{ __('admin.users.description') }}</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-col-number">{{ __('admin.columns.number') }}</th>
                            <th class="admin-col-email">
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['users_sort' => 'email', 'users_direction' => $sorts['users']['field'] === 'email' && $sorts['users']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.email') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['users']['field'] === 'email' ? ($sorts['users']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['users_sort' => 'created_at', 'users_direction' => $sorts['users']['field'] === 'created_at' && $sorts['users']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.registered_at') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['users']['field'] === 'created_at' ? ($sorts['users']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>{{ __('admin.columns.total_dictionaries') }}</th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['users_sort' => 'total_words', 'users_direction' => $sorts['users']['field'] === 'total_words' && $sorts['users']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.total_words') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['users']['field'] === 'total_words' ? ($sorts['users']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>{{ __('admin.columns.completed_sessions') }}</th>
                            <th>{{ __('admin.columns.accuracy') }}</th>
                            <th class="admin-col-actions">{{ __('admin.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="admin-col-number">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="admin-col-email">{{ $user->email }}</td>
                                <td>{{ optional($user->created_at)->format('d.m.Y H:i') }}</td>
                                <td>{{ (int) $user->owned_dictionaries_count }}</td>
                                <td>{{ (int) $user->owned_words_count }}</td>
                                <td>{{ (int) $user->completed_sessions_count }}</td>
                                <td>
                                    @if ($user->accuracy_percentage !== null)
                                        {{ (int) $user->accuracy_percentage }}%
                                    @else
                                        {{ __('admin.fallbacks.no_data') }}
                                    @endif
                                </td>
                                <td>
                                    @if (mb_strtolower((string) $user->email) === $adminEmail)
                                        <span
                                            class="admin-icon-btn admin-icon-btn--protected"
                                            title="{{ __('admin.actions.protected_account') }}"
                                            aria-label="{{ __('admin.actions.protected_account') }}"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 1.75a.75.75 0 0 1 .33.08l5 2.5a.75.75 0 0 1 .42.67v3.74c0 3.53-2.2 6.7-5.52 7.95a.75.75 0 0 1-.46 0C6.45 15.44 4.25 12.27 4.25 8.74V5a.75.75 0 0 1 .42-.67l5-2.5A.75.75 0 0 1 10 1.75Zm0 4a2 2 0 0 0-2 2v.5h-.25a.75.75 0 0 0-.75.75v2.5c0 .41.34.75.75.75h4.5a.75.75 0 0 0 .75-.75V9a.75.75 0 0 0-.75-.75H12v-.5a2 2 0 0 0-2-2Zm-.5 2a.5.5 0 0 1 1 0v.5h-1v-.5Z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            class="admin-icon-btn admin-icon-btn--danger"
                                            title="{{ __('admin.actions.delete_account') }}"
                                            aria-label="{{ __('admin.actions.delete_account') }}"
                                            @click="userToDelete = { id: {{ $user->id }}, email: {{ \Illuminate\Support\Js::from($user->email) }} }"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.5 3a1 1 0 0 0-1 1V5H5.75a.75.75 0 0 0 0 1.5h.53l.64 8.17A2 2 0 0 0 8.91 16.5h2.18a2 2 0 0 0 1.99-1.83l.64-8.17h.53a.75.75 0 0 0 0-1.5H12.5V4a1 1 0 0 0-1-1h-3ZM11 5V4.5h-2V5h2Zm-2.24 3.01a.75.75 0 0 1 .8.69l.25 4a.75.75 0 0 1-1.5.1l-.25-4a.75.75 0 0 1 .7-.8Zm2.48 0a.75.75 0 0 1 .7.8l-.25 4a.75.75 0 1 1-1.5-.1l.25-4a.75.75 0 0 1 .8-.69Z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="admin-table__empty">{{ __('admin.users.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $users->links() }}
            </div>
        </section>

        <section class="profile-card admin-card" id="admin-user-dictionaries-section">
            <div class="admin-section-heading">
                <div>
                    <h2 class="profile-section__title">{{ __('admin.dictionaries.title') }}</h2>
                    <p class="profile-section__description">{{ __('admin.dictionaries.description') }}</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-col-number">{{ __('admin.columns.number') }}</th>
                            <th class="admin-col-name">{{ __('admin.columns.dictionary_name') }}</th>
                            <th>{{ __('admin.columns.language') }}</th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['dictionaries_sort' => 'word_count', 'dictionaries_direction' => $sorts['dictionaries']['field'] === 'word_count' && $sorts['dictionaries']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.word_count') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['dictionaries']['field'] === 'word_count' ? ($sorts['dictionaries']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['dictionaries_sort' => 'owner_email', 'dictionaries_direction' => $sorts['dictionaries']['field'] === 'owner_email' && $sorts['dictionaries']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.owner_email') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['dictionaries']['field'] === 'owner_email' ? ($sorts['dictionaries']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['dictionaries_sort' => 'created_at', 'dictionaries_direction' => $sorts['dictionaries']['field'] === 'created_at' && $sorts['dictionaries']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.created_at') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['dictionaries']['field'] === 'created_at' ? ($sorts['dictionaries']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th class="admin-col-actions">{{ __('admin.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dictionaries as $dictionary)
                            <tr>
                                <td class="admin-col-number">{{ $dictionaries->firstItem() + $loop->index }}</td>
                                <td class="admin-col-name">{{ $dictionary->name }}</td>
                                <td>{{ $dictionary->language }}</td>
                                <td>{{ (int) $dictionary->words_count }}</td>
                                <td>{{ $dictionary->owner_email ?? '—' }}</td>
                                <td>{{ optional($dictionary->created_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="admin-actions-group">
                                        <a
                                            href="{{ route('dictionaries.show', $dictionary) }}"
                                            class="admin-icon-btn admin-icon-btn--neutral"
                                            title="{{ __('admin.actions.open_dictionary') }}"
                                            aria-label="{{ __('admin.actions.open_dictionary') }}"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10.75 3.5a.75.75 0 0 0 0 1.5h2.19L8.22 9.72a.75.75 0 1 0 1.06 1.06L14 6.06v2.19a.75.75 0 0 0 1.5 0V4.75a1.25 1.25 0 0 0-1.25-1.25h-3.5Z"/>
                                                <path d="M5.5 5.75A2.25 2.25 0 0 1 7.75 3.5h1a.75.75 0 0 1 0 1.5h-1A.75.75 0 0 0 7 5.75v6.5c0 .41.34.75.75.75h6.5a.75.75 0 0 0 .75-.75v-1a.75.75 0 0 1 1.5 0v1a2.25 2.25 0 0 1-2.25 2.25h-6.5A2.25 2.25 0 0 1 5.5 12.25v-6.5Z"/>
                                            </svg>
                                        </a>
                                        <button
                                            type="button"
                                            class="admin-icon-btn admin-icon-btn--danger"
                                            title="{{ __('admin.actions.delete_dictionary') }}"
                                            aria-label="{{ __('admin.actions.delete_dictionary') }}"
                                            @click="dictionaryToDelete = { id: {{ $dictionary->id }}, name: {{ \Illuminate\Support\Js::from($dictionary->name) }} }"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.5 3a1 1 0 0 0-1 1V5H5.75a.75.75 0 0 0 0 1.5h.53l.64 8.17A2 2 0 0 0 8.91 16.5h2.18a2 2 0 0 0 1.99-1.83l.64-8.17h.53a.75.75 0 0 0 0-1.5H12.5V4a1 1 0 0 0-1-1h-3ZM11 5V4.5h-2V5h2Zm-2.24 3.01a.75.75 0 0 1 .8.69l.25 4a.75.75 0 0 1-1.5.1l-.25-4a.75.75 0 0 1 .7-.8Zm2.48 0a.75.75 0 0 1 .7.8l-.25 4a.75.75 0 1 1-1.5-.1l.25-4a.75.75 0 0 1 .8-.69Z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="admin-table__empty">{{ __('admin.dictionaries.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $dictionaries->links() }}
            </div>
        </section>

        <section class="profile-card admin-card" id="admin-ready-dictionaries-section">
            <div class="admin-section-heading">
                <div>
                    <h2 class="profile-section__title">{{ __('admin.ready_dictionaries.title') }}</h2>
                    <p class="profile-section__description">{{ __('admin.ready_dictionaries.description') }}</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-col-number">{{ __('admin.columns.number') }}</th>
                            <th class="admin-col-name">{{ __('admin.columns.dictionary_name') }}</th>
                            <th>{{ __('admin.columns.language') }}</th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['ready_dictionaries_sort' => 'word_count', 'ready_dictionaries_direction' => $sorts['ready_dictionaries']['field'] === 'word_count' && $sorts['ready_dictionaries']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.word_count') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['ready_dictionaries']['field'] === 'word_count' ? ($sorts['ready_dictionaries']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th>{{ __('admin.columns.owner_email') }}</th>
                            <th>
                                <a href="{{ route('admin.index', array_merge(request()->query(), ['ready_dictionaries_sort' => 'created_at', 'ready_dictionaries_direction' => $sorts['ready_dictionaries']['field'] === 'created_at' && $sorts['ready_dictionaries']['direction'] === 'asc' ? 'desc' : 'asc'])) }}" class="admin-sort-link" data-admin-sort-link>
                                    {{ __('admin.columns.created_at') }}
                                    <span class="admin-sort-link__icon">{{ $sorts['ready_dictionaries']['field'] === 'created_at' ? ($sorts['ready_dictionaries']['direction'] === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                </a>
                            </th>
                            <th class="admin-col-actions">{{ __('admin.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($readyDictionaries as $readyDictionary)
                            <tr>
                                <td class="admin-col-number">{{ $readyDictionaries->firstItem() + $loop->index }}</td>
                                <td class="admin-col-name">{{ $readyDictionary->name }}</td>
                                <td>{{ $readyDictionary->language }}</td>
                                <td>{{ (int) $readyDictionary->words_count }}</td>
                                <td>{{ __('admin.ready_dictionaries.system_owner') }}</td>
                                <td>{{ optional($readyDictionary->created_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="admin-actions-group">
                                        <a
                                            href="{{ route('ready-dictionaries.show', $readyDictionary) }}"
                                            class="admin-icon-btn admin-icon-btn--neutral"
                                            title="{{ __('admin.actions.open_dictionary') }}"
                                            aria-label="{{ __('admin.actions.open_dictionary') }}"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10.75 3.5a.75.75 0 0 0 0 1.5h2.19L8.22 9.72a.75.75 0 1 0 1.06 1.06L14 6.06v2.19a.75.75 0 0 0 1.5 0V4.75a1.25 1.25 0 0 0-1.25-1.25h-3.5Z"/>
                                                <path d="M5.5 5.75A2.25 2.25 0 0 1 7.75 3.5h1a.75.75 0 0 1 0 1.5h-1A.75.75 0 0 0 7 5.75v6.5c0 .41.34.75.75.75h6.5a.75.75 0 0 0 .75-.75v-1a.75.75 0 0 1 1.5 0v1a2.25 2.25 0 0 1-2.25 2.25h-6.5A2.25 2.25 0 0 1 5.5 12.25v-6.5Z"/>
                                            </svg>
                                        </a>
                                        <button
                                            type="button"
                                            class="admin-icon-btn admin-icon-btn--danger"
                                            title="{{ __('admin.actions.delete_dictionary') }}"
                                            aria-label="{{ __('admin.actions.delete_dictionary') }}"
                                            @click="readyDictionaryToDelete = { id: {{ $readyDictionary->id }}, name: {{ \Illuminate\Support\Js::from($readyDictionary->name) }} }"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.5 3a1 1 0 0 0-1 1V5H5.75a.75.75 0 0 0 0 1.5h.53l.64 8.17A2 2 0 0 0 8.91 16.5h2.18a2 2 0 0 0 1.99-1.83l.64-8.17h.53a.75.75 0 0 0 0-1.5H12.5V4a1 1 0 0 0-1-1h-3ZM11 5V4.5h-2V5h2Zm-2.24 3.01a.75.75 0 0 1 .8.69l.25 4a.75.75 0 0 1-1.5.1l-.25-4a.75.75 0 0 1 .7-.8Zm2.48 0a.75.75 0 0 1 .7.8l-.25 4a.75.75 0 1 1-1.5-.1l.25-4a.75.75 0 0 1 .8-.69Z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="admin-table__empty">{{ __('admin.ready_dictionaries.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $readyDictionaries->links() }}
            </div>
        </section>

        <div
            class="dictionary-delete-overlay"
            x-cloak
            x-show="userToDelete !== null"
            x-transition.opacity
            @keydown.escape.window="userToDelete = null"
        >
            <div class="dictionary-delete-dialog" @click.self="userToDelete = null">
                <div class="dictionary-delete-modal profile-modal-form">
                    <h3 class="dictionary-delete-modal__title profile-modal-title">{{ __('admin.confirm.user_title') }}</h3>
                    <p class="dictionary-delete-modal__text profile-modal-text">
                        {{ __('admin.confirm.user_text') }}
                    </p>
                    <p class="admin-confirm-target" x-text="userToDelete?.email"></p>

                    <div class="dictionary-delete-modal__actions profile-modal-actions">
                        <button type="button" class="btn btn-secondary profile-modal-cancel-btn" @click="userToDelete = null">
                            {{ __('admin.actions.cancel') }}
                        </button>

                        <template x-if="userToDelete !== null">
                            <form :action="`{{ url('/admin/users') }}/${userToDelete.id}`" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dictionary-delete-modal__danger-btn profile-danger-btn">
                                    {{ __('admin.actions.confirm_delete') }}
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="dictionary-delete-overlay"
            x-cloak
            x-show="dictionaryToDelete !== null"
            x-transition.opacity
            @keydown.escape.window="dictionaryToDelete = null"
        >
            <div class="dictionary-delete-dialog" @click.self="dictionaryToDelete = null">
                <div class="dictionary-delete-modal profile-modal-form">
                    <h3 class="dictionary-delete-modal__title profile-modal-title">{{ __('admin.confirm.dictionary_title') }}</h3>
                    <p class="dictionary-delete-modal__text profile-modal-text">
                        {{ __('admin.confirm.dictionary_text') }}
                    </p>
                    <p class="admin-confirm-target" x-text="dictionaryToDelete?.name"></p>

                    <div class="dictionary-delete-modal__actions profile-modal-actions">
                        <button type="button" class="btn btn-secondary profile-modal-cancel-btn" @click="dictionaryToDelete = null">
                            {{ __('admin.actions.cancel') }}
                        </button>

                        <template x-if="dictionaryToDelete !== null">
                            <form :action="`{{ url('/admin/dictionaries') }}/${dictionaryToDelete.id}`" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dictionary-delete-modal__danger-btn profile-danger-btn">
                                    {{ __('admin.actions.confirm_delete') }}
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="dictionary-delete-overlay"
            x-cloak
            x-show="readyDictionaryToDelete !== null"
            x-transition.opacity
            @keydown.escape.window="readyDictionaryToDelete = null"
        >
            <div class="dictionary-delete-dialog" @click.self="readyDictionaryToDelete = null">
                <div class="dictionary-delete-modal profile-modal-form">
                    <h3 class="dictionary-delete-modal__title profile-modal-title">{{ __('admin.confirm.dictionary_title') }}</h3>
                    <p class="dictionary-delete-modal__text profile-modal-text">
                        {{ __('admin.confirm.ready_dictionary_text') }}
                    </p>
                    <p class="admin-confirm-target" x-text="readyDictionaryToDelete?.name"></p>

                    <div class="dictionary-delete-modal__actions profile-modal-actions">
                        <button type="button" class="btn btn-secondary profile-modal-cancel-btn" @click="readyDictionaryToDelete = null">
                            {{ __('admin.actions.cancel') }}
                        </button>

                        <template x-if="readyDictionaryToDelete !== null">
                            <form :action="`{{ url('/admin/ready-dictionaries') }}/${readyDictionaryToDelete.id}`" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dictionary-delete-modal__danger-btn profile-danger-btn">
                                    {{ __('admin.actions.confirm_delete') }}
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const storageKey = 'admin-sort-scroll-y';
        const savedScroll = sessionStorage.getItem(storageKey);

        if (savedScroll !== null) {
            sessionStorage.removeItem(storageKey);
            window.scrollTo({ top: Number(savedScroll), behavior: 'auto' });
        }

        document.querySelectorAll('[data-admin-sort-link]').forEach((link) => {
            link.addEventListener('click', () => {
                sessionStorage.setItem(storageKey, String(window.scrollY));
            });
        });
    });
</script>
@endsection
