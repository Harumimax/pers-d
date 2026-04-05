<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show" x-data="{ showCreateForm: false, word: '', translation: '', comment: '' }">
        <header class="dictionary-show__header">

            <div class="dictionary-show__title-row">
                <h1 class="dictionary-show__title">{{ $dictionary->name }}</h1>
                <button
                    type="button"
                    class="btn btn-primary dictionaries-new-btn dictionary-show__add-btn"
                    x-show="!showCreateForm"
                    x-on:click="showCreateForm = true"
                >
                    <span class="dictionaries-new-btn__plus">+</span>
                    <span>Add Word</span>
                </button>
            </div>

            <p class="dictionary-show__subtitle">
                Language <b>{{ $dictionary->language ?? 'not specified' }}</b> &middot; Created on {{ $dictionary->created_at?->format('Y-m-d') ?? 'unknown date' }}
            </p>
        </header>

        <section
            class="dictionaries-create-card dictionary-show__create-card"
            aria-label="Add word form"
            x-show="showCreateForm"
            x-cloak
        >
            <form
                class="dictionaries-create-form dictionary-show__create-form"
                x-on:submit.prevent
            >
                <div class="dictionaries-field">
                    <label for="word-name" class="dictionaries-label">Word</label>
                    <input
                        id="word-name"
                        type="text"
                        class="dictionaries-input"
                        placeholder="e.g., buongiorno"
                        x-model="word"
                    >
                </div>

                <div class="dictionaries-field">
                    <label for="word-translation" class="dictionaries-label">Translation</label>
                    <input
                        id="word-translation"
                        type="text"
                        class="dictionaries-input"
                        placeholder="e.g., good morning"
                        x-model="translation"
                    >
                </div>

                <div class="dictionaries-field dictionary-show__comment-field">
                    <label for="word-comment" class="dictionaries-label">Comment</label>
                    <input
                        id="word-comment"
                        type="text"
                        class="dictionaries-input"
                        placeholder="e.g., formal greeting"
                        x-model="comment"
                    >
                </div>

                <div class="dictionaries-create-actions dictionary-show__create-actions">
                    <button type="submit" class="btn btn-primary dictionaries-action-btn">
                        Add
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary dictionaries-action-btn"
                        x-on:click="showCreateForm = false; word = ''; translation = ''; comment = ''"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </section>

        <article class="dictionary-show-card" aria-label="Dictionary details">
            <h2 class="dictionary-show-card__title">Word List
            </h2>
        </article>
    </section>
</main>
