<?php

namespace App\Livewire\Dictionaries;

use App\Models\User;
use App\Models\Word;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';
    public string $language = '';
    public bool $showCreateForm = false;
    public int $formRenderKey = 0;
    public ?int $pendingDeleteDictionaryId = null;
    public string $pendingDeleteDictionaryLabel = '';

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function cancelCreate(): void
    {
        $this->reset(['showCreateForm', 'name', 'language']);
        $this->resetValidation();
        $this->formRenderKey++;
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
        $this->resetValidation();
        $this->formRenderKey++;
    }

    public function confirmDeleteDictionary(int $dictionaryId): void
    {
        $user = $this->currentUser();

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        $this->pendingDeleteDictionaryId = $dictionary->id;
        $this->pendingDeleteDictionaryLabel = $dictionary->name;
    }

    public function cancelDeleteDictionary(): void
    {
        $this->pendingDeleteDictionaryId = null;
        $this->pendingDeleteDictionaryLabel = '';
    }

    public function deleteConfirmedDictionary(): void
    {
        $user = $this->currentUser();

        $dictionaryId = $this->pendingDeleteDictionaryId;
        abort_if($dictionaryId === null, 404);

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        DB::transaction(function () use ($dictionary): void {
            $wordIds = $dictionary->words()
                ->pluck('words.id')
                ->all();

            $dictionary->delete();

            if ($wordIds !== []) {
                Word::query()->whereIn('id', $wordIds)->delete();
            }
        });

        $this->cancelDeleteDictionary();
    }

    public function render(): View
    {
        $user = $this->currentUser();

        /** @var Collection<int, \App\Models\UserDictionary> $dictionaries */
        $dictionaries = $user->dictionaries()
            ->withCount('words')
            ->orderByDesc('created_at')
            ->get();

        /** @var Collection<int, \App\Models\UserDictionary> $headerDictionaries */
        $headerDictionaries = $user->dictionaries()
            ->orderByDesc('created_at')
            ->get(['id', 'name']);

        return view('livewire.dictionaries.index', [
            'dictionaries' => $dictionaries,
        ])->layout('layouts.dictionaries', [
            'headerDictionaries' => $headerDictionaries,
        ]);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
