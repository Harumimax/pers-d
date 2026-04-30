<div class="dictionary-card tg-bot-card">
    <div class="dictionary-card__content" x-data="{ intervalExpanded: false }">
        <div class="tg-bot-card__header">
            <div>
                <h2 class="dictionary-card__title">{{ __('tg-bot.interval_review.title') }}</h2>
                <p class="dictionary-card__meta tg-bot-card__description">{{ __('tg-bot.interval_review.description') }}</p>
            </div>

            <button
                type="button"
                class="tg-bot-card__toggle"
                @click="intervalExpanded = ! intervalExpanded"
                :aria-expanded="intervalExpanded.toString()"
            >
                <span class="sr-only">{{ __('tg-bot.interval_review.toggle') }}</span>
                <svg
                    class="tg-bot-card__toggle-icon"
                    :class="{ 'tg-bot-card__toggle-icon--expanded': intervalExpanded }"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        <div x-show="intervalExpanded" x-cloak class="tg-bot-interval">
            <section class="tg-bot-form__section">
                <div class="tg-bot-form__switch-row">
                    <div>
                        <h3 class="tg-bot-form__switch-title">{{ __('tg-bot.interval_review.enabled') }}</h3>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.enabled_hint') }}</p>
                    </div>

                    <label class="tg-bot-switch">
                        <input type="checkbox" wire:model.live="enabled">
                        <span class="tg-bot-switch__track" aria-hidden="true"></span>
                    </label>
                </div>

                <div class="tg-bot-interval__top-grid">
                    <div class="tg-bot-form__field">
                        <label for="interval-language" class="tg-bot-form__label">{{ __('tg-bot.interval_review.language.label') }}</label>
                        <select id="interval-language" class="tg-bot-form__control" wire:model.live="selectedLanguage">
                            @foreach ($languageOptions as $languageOption)
                                <option value="{{ $languageOption['value'] }}">{{ $languageOption['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.language.hint') }}</p>
                    </div>

                    <div class="tg-bot-form__field">
                        <label for="interval-start-time" class="tg-bot-form__label">{{ __('tg-bot.interval_review.start_time.label') }}</label>
                        <input id="interval-start-time" type="time" class="tg-bot-form__control" wire:model.live="startTime">
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.start_time.hint') }}</p>
                        @error('interval_review_start_time')
                            <p class="tg-bot-inline-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tg-bot-form__field">
                        <span class="tg-bot-form__label">{{ __('tg-bot.interval_review.timezone.label') }}</span>
                        <div class="tg-bot-interval__timezone">{{ $timezone }}</div>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.timezone.hint') }}</p>
                    </div>
                </div>
            </section>

            <section class="tg-bot-form__section">
                <div>
                    <h3 class="tg-bot-form__section-title">{{ __('tg-bot.interval_review.dictionary_picker.title') }}</h3>
                    <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.dictionary_picker.hint') }}</p>
                </div>

                <div class="tg-bot-interval__dictionary-columns">
                    <div class="tg-bot-interval__dictionary-column">
                        <h4 class="tg-bot-interval__dictionary-title">{{ __('tg-bot.interval_review.dictionary_picker.user_title') }}</h4>

                        @if ($userDictionaries->isNotEmpty())
                            <div class="tg-bot-interval__dictionary-list">
                                @foreach ($userDictionaries as $dictionary)
                                    @php
                                        $languageKey = $dictionary->language !== null
                                            ? 'dictionaries.index.languages.' . strtolower($dictionary->language)
                                            : 'dictionaries.index.languages.not_specified';
                                    @endphp
                                    <button
                                        type="button"
                                        class="tg-bot-interval__dictionary-button"
                                        wire:click="openDictionary('user', {{ $dictionary->id }})"
                                    >
                                        <span class="tg-bot-interval__dictionary-name">{{ $dictionary->name }}</span>
                                        <span class="tg-bot-interval__dictionary-meta">
                                            {{ __($languageKey) }}
                                            <span aria-hidden="true">&middot;</span>
                                            {{ trans_choice('remainder.settings.dictionaries.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.dictionary_picker.empty_user') }}</p>
                        @endif
                    </div>

                    <div class="tg-bot-interval__dictionary-column">
                        <h4 class="tg-bot-interval__dictionary-title">{{ __('tg-bot.interval_review.dictionary_picker.ready_title') }}</h4>

                        @if ($readyDictionaries->isNotEmpty())
                            <div class="tg-bot-interval__dictionary-list">
                                @foreach ($readyDictionaries as $dictionary)
                                    @php
                                        $languageKey = $dictionary->language !== null
                                            ? 'dictionaries.index.languages.' . strtolower($dictionary->language)
                                            : 'dictionaries.index.languages.not_specified';
                                    @endphp
                                    <button
                                        type="button"
                                        class="tg-bot-interval__dictionary-button"
                                        wire:click="openDictionary('ready', {{ $dictionary->id }})"
                                    >
                                        <span class="tg-bot-interval__dictionary-name">{{ $dictionary->name }}</span>
                                        <span class="tg-bot-interval__dictionary-meta">
                                            {{ __($languageKey) }}
                                            <span aria-hidden="true">&middot;</span>
                                            {{ trans_choice('remainder.settings.dictionaries.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.dictionary_picker.empty_ready') }}</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="tg-bot-form__section">
                <div class="tg-bot-interval__selected-header">
                    <div>
                        <h3 class="tg-bot-form__section-title">{{ __('tg-bot.interval_review.selected_words.title') }}</h3>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.selected_words.counter', ['count' => $selectedWordsCount, 'max' => 20]) }}</p>
                    </div>
                    <span class="tg-bot-interval__selected-counter">{{ $selectedWordsCount }}/20</span>
                </div>

                @error('selection_limit')
                    <div class="tg-bot-alert tg-bot-alert--error" role="alert">{{ $message }}</div>
                @enderror

                @error('interval_review_words')
                    <div class="tg-bot-alert tg-bot-alert--error" role="alert">{{ $message }}</div>
                @enderror

                @if ($selectedWordGroups !== [])
                    <div class="tg-bot-interval__selected-groups">
                        @foreach ($selectedWordGroups as $sourceLabel => $dictionaryGroups)
                            <section class="tg-bot-interval__selected-group">
                                <h4 class="tg-bot-interval__selected-source">{{ $sourceLabel }}</h4>

                                @foreach ($dictionaryGroups as $dictionaryName => $words)
                                    <div class="tg-bot-interval__selected-dictionary">
                                        <h5 class="tg-bot-interval__selected-dictionary-title">{{ $dictionaryName }}</h5>

                                        <div class="tg-bot-interval__selected-word-list">
                                            @foreach ($words as $word)
                                                <article class="tg-bot-interval__selected-word" wire:key="selected-word-{{ $word['selection_key'] }}">
                                                    <div>
                                                        <div class="tg-bot-interval__selected-word-title">{{ $word['word'] }}</div>
                                                        <div class="tg-bot-interval__selected-word-meta">
                                                            {{ $word['translation'] }}
                                                            @if ($word['part_of_speech'])
                                                                <span aria-hidden="true">&middot;</span>
                                                                {{ $word['part_of_speech'] }}
                                                            @endif
                                                        </div>
                                                        @if ($word['comment'])
                                                            <div class="tg-bot-interval__selected-word-comment">{{ $word['comment'] }}</div>
                                                        @endif
                                                    </div>

                                                    <button
                                                        type="button"
                                                        class="tg-bot-interval__remove-word"
                                                        x-on:click="$el.blur()"
                                                        wire:click.prevent="removeSelectedWord('{{ $word['selection_key'] }}')"
                                                    >
                                                        {{ __('tg-bot.interval_review.selected_words.remove') }}
                                                    </button>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                @else
                    <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.selected_words.empty') }}</p>
                @endif
            </section>

            <div class="tg-bot-form__actions">
                <button type="button" class="btn btn-primary tg-bot-form__submit" wire:click="buildPlanPreview">
                    {{ __('tg-bot.interval_review.preview.action') }}
                </button>
                <button type="button" class="btn btn-secondary tg-bot-form__submit" disabled aria-disabled="true">
                    {{ __('tg-bot.interval_review.preview.apply_action') }}
                </button>
            </div>
            <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.preview.apply_hint') }}</p>

            @if ($planPreviewVisible)
                <section class="tg-bot-form__section tg-bot-interval__preview-section">
                    <div>
                        <h3 class="tg-bot-form__section-title">{{ __('tg-bot.interval_review.preview.title') }}</h3>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.preview.hint') }}</p>
                    </div>

                    <div class="tg-bot-interval__schedule-card">
                        <h4 class="tg-bot-interval__schedule-title">{{ __('tg-bot.interval_review.preview.schedule_title') }}</h4>
                        <div class="tg-bot-interval__schedule-list">
                            @foreach ($schedulePreview as $session)
                                <div class="tg-bot-interval__schedule-item">
                                    <span class="tg-bot-interval__schedule-label">{{ $session['label'] }}</span>
                                    <span class="tg-bot-interval__schedule-date">{{ $session['scheduled_at_local'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tg-bot-interval__schedule-card">
                        <h4 class="tg-bot-interval__schedule-title">{{ __('tg-bot.interval_review.preview.first_session_title') }}</h4>
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.preview.first_session_hint', ['count' => count($firstSessionPreviewWords)]) }}</p>

                        <div class="tg-bot-interval__first-session-list">
                            @foreach ($firstSessionPreviewWords as $word)
                                <div class="tg-bot-interval__first-session-word" wire:key="preview-word-{{ $word['selection_key'] }}">
                                    <div class="tg-bot-interval__selected-word-title">{{ $word['word'] }}</div>
                                    <div class="tg-bot-interval__selected-word-meta">
                                        {{ $word['translation'] }}
                                        @if ($word['part_of_speech'])
                                            <span aria-hidden="true">&middot;</span>
                                            {{ $word['part_of_speech'] }}
                                        @endif
                                    </div>
                                    @if ($word['comment'])
                                        <div class="tg-bot-interval__selected-word-comment">{{ $word['comment'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tg-bot-form__actions">
                        <button type="button" class="btn btn-secondary tg-bot-form__submit" disabled aria-disabled="true">
                            {{ __('tg-bot.interval_review.preview.apply_action') }}
                        </button>
                    </div>
                    <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.preview.apply_hint') }}</p>
                </section>
            @endif
        </div>

        @if ($modalOpen && $modalDictionary)
            <div class="tg-bot-modal-backdrop" wire:click="closeDictionary"></div>
            <section class="tg-bot-modal" role="dialog" aria-modal="true" aria-label="{{ $modalDictionary->name }}">
                <div class="tg-bot-modal__card" wire:click.stop>
                    <div class="tg-bot-modal__header">
                        <div>
                            <h3 class="tg-bot-modal__title">{{ $modalDictionary->name }}</h3>
                            <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.modal.subtitle') }}</p>
                        </div>

                        <button type="button" class="tg-bot-modal__close" wire:click="closeDictionary">×</button>
                    </div>

                    <div class="tg-bot-modal__toolbar">
                        <div class="tg-bot-form__field">
                            <label class="tg-bot-form__label" for="interval-modal-search">{{ __('tg-bot.interval_review.modal.search_label') }}</label>
                            <input id="interval-modal-search" type="text" class="tg-bot-form__control" wire:model.live.debounce.250ms="modalSearch">
                        </div>

                        <div class="tg-bot-form__field">
                            <label class="tg-bot-form__label" for="interval-modal-filter">{{ __('tg-bot.interval_review.modal.filter_label') }}</label>
                            <select id="interval-modal-filter" class="tg-bot-form__control" wire:model.live="modalPartOfSpeech">
                                @foreach ($partOfSpeechOptions as $value => $label)
                                    <option value="{{ $value }}">{!! $label !!}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="tg-bot-modal__actions">
                        <button type="button" class="tg-bot-modal__bulk-button" wire:click="selectAllVisibleWords">{{ __('tg-bot.interval_review.modal.select_page') }}</button>
                        <button type="button" class="tg-bot-modal__bulk-button" wire:click="clearVisibleWordsSelection">{{ __('tg-bot.interval_review.modal.clear_page') }}</button>
                    </div>

                    @if ($modalWords['items'] !== [])
                        <div class="tg-bot-modal__table-wrap">
                            <table class="tg-bot-modal__table">
                                <thead>
                                    <tr>
                                        <th>{{ __('tg-bot.interval_review.modal.table.select') }}</th>
                                        <th>{{ __('tg-bot.interval_review.modal.table.word') }}</th>
                                        <th>{{ __('tg-bot.interval_review.modal.table.part_of_speech') }}</th>
                                        <th>{{ __('tg-bot.interval_review.modal.table.translation') }}</th>
                                        <th>{{ __('tg-bot.interval_review.modal.table.comment') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modalWords['items'] as $word)
                                        <tr wire:key="interval-modal-word-{{ $word['selection_key'] }}">
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    class="tg-bot-select-all__input"
                                                    @checked($this->isWordSelected($word['selection_key']))
                                                    wire:click="toggleWordSelection('{{ $word['source'] }}', {{ $word['dictionary_id'] }}, {{ $word['word_id'] }})"
                                                >
                                            </td>
                                            <td>{{ $word['word'] }}</td>
                                            <td>{{ $word['part_of_speech'] ?? '—' }}</td>
                                            <td>{{ $word['translation'] }}</td>
                                            <td>{{ $word['comment'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tg-bot-modal__pagination">
                            <p class="tg-bot-form__hint">
                                {{ __('tg-bot.interval_review.modal.pagination', ['from' => $modalWords['from'], 'to' => $modalWords['to'], 'total' => $modalWords['total']]) }}
                            </p>

                            <div class="tg-bot-modal__pagination-buttons">
                                <button type="button" class="tg-bot-modal__page-button" wire:click="previousModalPage" @disabled($modalWords['current_page'] <= 1)>
                                    {{ __('dictionaries.show.word_list.pagination.prev') }}
                                </button>

                                @for ($page = 1; $page <= $modalWords['last_page']; $page++)
                                    <button
                                        type="button"
                                        class="tg-bot-modal__page-button {{ $modalWords['current_page'] === $page ? 'is-active' : '' }}"
                                        wire:click="gotoModalPage({{ $page }})"
                                    >
                                        {{ $page }}
                                    </button>
                                @endfor

                                <button type="button" class="tg-bot-modal__page-button" wire:click="nextModalPage" @disabled($modalWords['current_page'] >= $modalWords['last_page'])>
                                    {{ __('dictionaries.show.word_list.pagination.next') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="tg-bot-form__hint">{{ __('tg-bot.interval_review.modal.empty') }}</p>
                    @endif
                </div>
            </section>
        @endif
    </div>
</div>
