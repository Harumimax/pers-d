<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        <header class="dictionary-show__header">
            <a href="{{ route('dictionaries.index') }}" class="dictionary-show__back-link">
                &larr; Back to dictionaries
            </a>
            <h1 class="dictionary-show__title">{{ $dictionary->name }}</h1>
            <p class="dictionary-show__subtitle">
                Language {{ $dictionary->language ?? 'not specified' }} &middot; Created on {{ $dictionary->created_at?->format('Y-m-d') ?? 'unknown date' }}
            </p>
        </header>

        <article class="dictionary-show-card" aria-label="Dictionary details">
            <h2 class="dictionary-show-card__title">Details</h2>

            <dl class="dictionary-show-meta">
                <div class="dictionary-show-meta__item">
                    <dt>Name</dt>
                    <dd>{{ $dictionary->name }}</dd>
                </div>

                <div class="dictionary-show-meta__item">
                    <dt>Language</dt>
                    <dd>{{ $dictionary->language ?? 'Language not specified' }}</dd>
                </div>

                <div class="dictionary-show-meta__item">
                    <dt>Created</dt>
                    <dd>{{ $dictionary->created_at?->format('Y-m-d') ?? 'Unknown date' }}</dd>
                </div>
            </dl>
        </article>
    </section>
</main>
