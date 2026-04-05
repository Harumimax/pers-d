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

    private const SORT_NEWEST = 'newest';
    private const SORT_OLDEST = 'oldest';
    private const SORT_A_TO_Z = 'a-z';

    public UserDictionary $dictionary;
    public string $word = '';
    public string $translation = '';
    public string $comment = '';
    public string $sort = self::SORT_NEWEST;
    public bool $showCreateForm = false;
    public int $formRenderKey = 0;
    public ?int $pendingDeleteWordId = null;
    public string $pendingDeleteWordLabel = '';

    public function mount(UserDictionary $dictionary): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);
        abort_if($dictionary->user_id !== $user->id, 403);

        $this->dictionary = $dictionary;
    }

    public function render(): View
    {
        $wordsQuery = $this->dictionary->words();

        if ($this->sort === self::SORT_OLDEST) {
            $wordsQuery->orderBy('user_dictionary_word.created_at');
        } elseif ($this->sort === self::SORT_A_TO_Z) {
            $wordsQuery->orderBy('words.word');
        } else {
            $wordsQuery->orderByDesc('user_dictionary_word.created_at');
        }

        $words = $wordsQuery
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
        $this->resetValidation();
        $this->formRenderKey++;
        $this->showCreateForm = true;
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function updatedSort(string $value): void
    {
        if (! in_array($value, [
            self::SORT_NEWEST,
            self::SORT_OLDEST,
            self::SORT_A_TO_Z,
        ], true)) {
            $this->sort = self::SORT_NEWEST;
        }

        $this->resetPage();
    }

    public function cancelCreate(): void
    {
        $this->reset(['showCreateForm', 'word', 'translation', 'comment']);
        $this->resetValidation();
        $this->formRenderKey++;
    }

    public function confirmDeleteWord(int $wordId): void
    {
        $isAttached = $this->dictionary->words()
            ->where('words.id', $wordId)
            ->exists();

        abort_if(! $isAttached, 403);

        $word = Word::query()->findOrFail($wordId);
        $this->pendingDeleteWordId = $wordId;
        $this->pendingDeleteWordLabel = $word->word;

    }

    public function cancelDeleteWord(): void
    {
        $this->pendingDeleteWordId = null;
        $this->pendingDeleteWordLabel = '';
    }

    public function deleteConfirmedWord(): void
    {
        $wordId = $this->pendingDeleteWordId;
        abort_if($wordId === null || $wordId <= 0, 404);

        $isAttached = $this->dictionary->words()
            ->where('words.id', $wordId)
            ->exists();

        abort_if(! $isAttached, 403);

        $this->dictionary->words()->detach($wordId);
        Word::query()->whereKey($wordId)->delete();

        $this->cancelDeleteWord();

        $currentPage = $this->getPage();
        $totalAfterDelete = $this->dictionary->words()->count();
        $maxPage = max(1, (int) ceil($totalAfterDelete / 20));

        if ($currentPage > $maxPage) {
            $this->setPage($maxPage);
        }
    }
}
