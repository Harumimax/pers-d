@props([
    'examples' => collect(),
    'word' => '',
])

@php
    $exampleItems = $examples instanceof \Illuminate\Support\Collection ? $examples->take(3) : collect($examples)->take(3);
@endphp

@if ($exampleItems->isNotEmpty())
    <div class="word-example-hint">
        <button
            type="button"
            class="word-example-hint__trigger"
            data-word-example-trigger
            aria-label="{{ __('dictionaries.show.word_list.examples.aria', ['word' => $word]) }}"
            title="{{ __('dictionaries.show.word_list.examples.title') }}"
            aria-expanded="false"
        >
            <span aria-hidden="true">ⓘ</span>
        </button>

        <div class="word-example-hint__popover" role="tooltip" hidden data-word-example-popover>
            @foreach ($exampleItems as $example)
                <div class="word-example-hint__item">
                    <p class="word-example-hint__text">{{ $example->example_text }}</p>
                    <p class="word-example-hint__text">{{ $example->example_translation }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif
