<?php

namespace App\Livewire\Dictionaries;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $name = '';

    public function createDictionary(): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user_dictionaries', 'name')->where(
                    fn ($query) => $query->where('user_id', $user->id)
                ),
            ],
        ]);

        $user->dictionaries()->create($validated);

        $this->reset('name');
    }

    public function deleteDictionary(int $dictionaryId): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        $dictionary->delete();
    }

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);

        /** @var Collection<int, \App\Models\UserDictionary> $dictionaries */
        $dictionaries = $user->dictionaries()
            ->orderBy('name')
            ->get();

        return view('livewire.dictionaries.index', [
            'dictionaries' => $dictionaries,
        ]);
    }
}
