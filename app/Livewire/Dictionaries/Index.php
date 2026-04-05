<?php

namespace App\Livewire\Dictionaries;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dictionaries')]
class Index extends Component
{
    public string $name = '';
    public string $language = '';
    public bool $showCreateForm = false;

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function cancelCreate(): void
    {
        $this->reset(['showCreateForm', 'name', 'language']);
    }

    public function createDictionary(): void
    {
        $user = $this->currentUser();

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user_dictionaries', 'name')->where(
                    fn ($query) => $query->where('user_id', $user->id)
                ),
            ],
            'language' => [
                'required',
                Rule::in(['English', 'Spanish']),
            ],
        ]);

        $user->dictionaries()->create($validated);

        $this->reset(['name', 'language']);
    }

    public function deleteDictionary(int $dictionaryId): void
    {
        $user = $this->currentUser();

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        $dictionary->delete();
    }

    public function render(): View
    {
        $user = $this->currentUser();

        /** @var Collection<int, \App\Models\UserDictionary> $dictionaries */
        $dictionaries = $user->dictionaries()
            ->withCount('words')
            ->orderBy('name')
            ->get();

        return view('livewire.dictionaries.index', [
            'dictionaries' => $dictionaries,
        ]);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
