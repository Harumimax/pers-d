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
            <h2 class="dictionary-show-card__title">Word List
            </h2>


        </article>
    </section>
</main>
