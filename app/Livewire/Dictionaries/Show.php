<?php

namespace App\Livewire\Dictionaries;

use App\Models\Word;
use App\Models\UserDictionary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dictionaries')]
class Show extends Component
{
    use WithPagination;

    public UserDictionary $dictionary;
    public string $word = '';
    public string $translation = '';
    public string $comment = '';
    public bool $showCreateForm = false;

    public function mount(UserDictionary $dictionary): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);
        abort_if($dictionary->user_id !== $user->id, 403);

        $this->dictionary = $dictionary;
    }

    public function render(): View
    {
        $words = $this->dictionary->words()
            ->orderByDesc('user_dictionary_word.created_at')
            ->paginate(20);

        return view('livewire.dictionaries.show', [
            'words' => $words,
        ]);
    }

    public function addWord(): void
    {
        $validated = $this->validate([
            'word' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:600'],
        ]);

        $word = Word::create($validated);
        $this->dictionary->words()->attach($word->id);

        $this->reset(['word', 'translation', 'comment']);
        $this->showCreateForm = true;
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function cancelCreate(): void
    {
        $this->reset(['showCreateForm', 'word', 'translation', 'comment']);
    }

    public function deleteWord(int $wordId): void
    {
        $isAttached = $this->dictionary->words()
            ->where('words.id', $wordId)
            ->exists();

        abort_if(! $isAttached, 403);

        $this->dictionary->words()->detach($wordId);
        Word::query()->whereKey($wordId)->delete();

        $currentPage = $this->getPage();
        $totalAfterDelete = $this->dictionary->words()->count();
        $maxPage = max(1, (int) ceil($totalAfterDelete / 20));

        if ($currentPage > $maxPage) {
            $this->setPage($maxPage);
        }
    }
}
