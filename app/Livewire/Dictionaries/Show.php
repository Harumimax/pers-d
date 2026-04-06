<?php

namespace App\Livewire\Dictionaries;

use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dictionaries')]
class Show extends Component
{
    use WithPagination;

    private const PART_OF_SPEECH_FILTER_ALL = 'all';
    private const SORT_NEWEST = 'newest';
    private const SORT_OLDEST = 'oldest';
    private const SORT_A_TO_Z = 'a-z';
    private const PARTS_OF_SPEECH = [
        'noun',
        'verb',
        'adjective',
        'adverb',
        'pronoun',
        'preposition',
        'conjunction',
        'interjection',
        'stable_expression',
    ];
    private const PARTS_OF_SPEECH_DISPLAY = [
        'noun' => 'Noun',
        'verb' => 'Verb',
        'adjective' => 'Adjective',
        'adverb' => 'Adverb',
        'pronoun' => 'Pronoun',
        'preposition' => 'Preposition',
        'conjunction' => 'Conjunction',
        'interjection' => 'Interjection',
        'stable_expression' => 'Stable expression',
    ];

    public UserDictionary $dictionary;
    public string $word = '';
    public string $partOfSpeech = '';
    public string $partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
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
        $totalWordsCount = $this->dictionary->words()->count();
        $wordsQuery = $this->dictionary->words();
        $partOfSpeechOptions = $this->partOfSpeechOptions();

        if ($this->partOfSpeechFilter !== self::PART_OF_SPEECH_FILTER_ALL) {
            $wordsQuery->where('words.part_of_speech', $this->partOfSpeechFilter);
        }

        if ($this->sort === self::SORT_OLDEST) {
            $wordsQuery->orderBy('user_dictionary_word.created_at');
        } elseif ($this->sort === self::SORT_A_TO_Z) {
            $wordsQuery->orderBy('words.word');
        } else {
            $wordsQuery->orderByDesc('user_dictionary_word.created_at');
        }

        $words = $wordsQuery->paginate(20);

        return view('livewire.dictionaries.show', [
            'words' => $words,
            'totalWordsCount' => $totalWordsCount,
            'partOfSpeechOptions' => $partOfSpeechOptions,
            'partOfSpeechFilterOptions' => [
                self::PART_OF_SPEECH_FILTER_ALL => '&#1042;&#1089;&#1077; (All)',
                ...$partOfSpeechOptions,
            ],
            'partOfSpeechDisplayMap' => self::PARTS_OF_SPEECH_DISPLAY,
        ]);
    }

    public function addWord(): void
    {
        $validated = $this->validate([
            'word' => ['required', 'string', 'max:255'],
            'partOfSpeech' => ['required', 'string', Rule::in(self::PARTS_OF_SPEECH)],
            'translation' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:600'],
        ]);

        $word = Word::create([
            'word' => $validated['word'],
            'part_of_speech' => $validated['partOfSpeech'],
            'translation' => $validated['translation'],
            'comment' => $validated['comment'],
        ]);

        $this->dictionary->words()->attach($word->id);

        $this->reset(['word', 'partOfSpeech', 'translation', 'comment']);
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

    public function updatedPartOfSpeechFilter(string $value): void
    {
        $allowedValues = [
            self::PART_OF_SPEECH_FILTER_ALL,
            ...self::PARTS_OF_SPEECH,
        ];

        if (! in_array($value, $allowedValues, true)) {
            $this->partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
        }

        $this->resetPage();
    }

    public function cancelCreate(): void
    {
        $this->reset(['showCreateForm', 'word', 'partOfSpeech', 'translation', 'comment']);
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

    private function partOfSpeechOptions(): array
    {
        return [
            'noun' => '&#1057;&#1091;&#1097;&#1077;&#1089;&#1090;&#1074;&#1080;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077; (Noun)',
            'verb' => '&#1043;&#1083;&#1072;&#1075;&#1086;&#1083; (Verb)',
            'adjective' => '&#1055;&#1088;&#1080;&#1083;&#1072;&#1075;&#1072;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077; (Adjective)',
            'adverb' => '&#1053;&#1072;&#1088;&#1077;&#1095;&#1080;&#1077; (Adverb)',
            'pronoun' => '&#1052;&#1077;&#1089;&#1090;&#1086;&#1080;&#1084;&#1077;&#1085;&#1080;&#1077; (Pronoun)',
            'preposition' => '&#1055;&#1088;&#1077;&#1076;&#1083;&#1086;&#1075; (Preposition)',
            'conjunction' => '&#1057;&#1086;&#1102;&#1079; (Conjunction)',
            'interjection' => '&#1052;&#1077;&#1078;&#1076;&#1086;&#1084;&#1077;&#1090;&#1080;&#1077; (Interjection)',
            'stable_expression' => '&#1059;&#1089;&#1090;&#1086;&#1081;&#1095;&#1080;&#1074;&#1086;&#1077; &#1074;&#1099;&#1088;&#1072;&#1078;&#1077;&#1085;&#1080;&#1077; (Stable expression)',
        ];
    }
}
