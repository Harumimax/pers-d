<div>
    <h1>Your dictionaries</h1>

    <form wire:submit="createDictionary">
        <label for="name">Dictionary name</label>
        <input
            id="name"
            type="text"
            wire:model="name"
        >

        @error('name')
            <div>{{ $message }}</div>
        @enderror

        <button type="submit">Create dictionary</button>
    </form>

    <ul>
        @forelse ($dictionaries as $dictionary)
            <li wire:key="dictionary-{{ $dictionary->id }}">
                <a href="{{ route('dictionaries.show', $dictionary) }}">
                    {{ $dictionary->name }}
                </a>

                <button
                    type="button"
                    wire:click="deleteDictionary({{ $dictionary->id }})"
                >
                    Delete
                </button>
            </li>
        @empty
            <li>No dictionaries yet.</li>
        @endforelse
    </ul>
</div>
