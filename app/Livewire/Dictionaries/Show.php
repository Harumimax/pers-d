<?php

namespace App\Livewire\Dictionaries;

use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    private const PART_OF_SPEECH_FILTER_ALL = 'all';
    private const SORT_NEWEST = 'newest';
    private const SORT_OLDEST = 'oldest';
    private const SORT_A_TO_Z = 'a-z';
    private const TARGET_LANGUAGE = 'ru';
    private const CONTROL_CHARACTER_PATTERN = '/[\p{Cc}\p{Cf}]/u';
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';
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
    private const DICTIONARY_LANGUAGE_CODES = [
        'English' => 'en',
        'Spanish' => 'es',
    ];

    public UserDictionary $dictionary;
    public string $word = '';
    public string $partOfSpeech = '';
    public string $autoWord = '';
    public string $autoPartOfSpeech = '';
    public string $autoTranslation = '';
    public string $autoComment = '';
    /** @var array<int, array{text: string, label: string}> */
    public array $autoSuggestions = [];
    public bool $autoTranslated = false;
    public string $autoTranslationError = '';
    public string $partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
    public string $search = '';
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
        $user = $this->currentUser();
        $totalWordsCount = $this->dictionary->words()->count();
        $wordsQuery = $this->dictionary->words();
        $partOfSpeechOptions = $this->partOfSpeechOptions();
        $searchTerm = trim($this->search);
        $normalizedSearchTerm = mb_strtolower($searchTerm);
        /** @var Collection<int, \App\Models\UserDictionary> $headerDictionaries */
        $headerDictionaries = $user->dictionaries()
            ->orderByDesc('created_at')
            ->get(['id', 'name']);

        if ($this->partOfSpeechFilter !== self::PART_OF_SPEECH_FILTER_ALL) {
            $wordsQuery->where('words.part_of_speech', $this->partOfSpeechFilter);
        }

        if ($searchTerm !== '') {
            $wordsQuery->where(function ($query) use ($normalizedSearchTerm): void {
                $query->whereRaw('LOWER(words.word) LIKE ?', ['%'.$normalizedSearchTerm.'%'])
                    ->orWhereRaw('LOWER(words.translation) LIKE ?', ['%'.$normalizedSearchTerm.'%']);
            });
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
                self::PART_OF_SPEECH_FILTER_ALL => 'All (&#1042;&#1089;&#1077;)',
                ...$partOfSpeechOptions,
            ],
            'partOfSpeechDisplayMap' => self::PARTS_OF_SPEECH_DISPLAY,
            'autoTranslationUnavailableMessage' => 'Translation is currently unavailable. Please switch to Enter manually.',
        ])->layout('layouts.dictionaries', [
            'headerDictionaries' => $headerDictionaries,
        ]);
    }

    public function addWord(): void
    {
        $this->word = $this->sanitizeTextInput($this->word);
        $this->translation = $this->sanitizeTextInput($this->translation);
        $this->comment = $this->sanitizeTextInput($this->comment);

        $validated = $this->validate([
            'word' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            'partOfSpeech' => ['required', 'string', Rule::in(self::PARTS_OF_SPEECH)],
            'translation' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            'comment' => ['nullable', 'string', 'max:600', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
        ]);

        $this->storeWord(
            $validated['word'],
            $validated['partOfSpeech'],
            $validated['translation'],
            $validated['comment'] ?? null,
        );
        $this->reset(['word', 'partOfSpeech', 'translation', 'comment']);
        $this->resetValidation();
        $this->formRenderKey++;
        $this->showCreateForm = true;
        $this->resetPage();
    }

    public function addTranslatedWord(): void
    {
        $this->autoWord = $this->sanitizeTextInput($this->autoWord);
        $this->autoTranslation = $this->sanitizeTextInput($this->autoTranslation);
        $this->autoComment = $this->sanitizeTextInput($this->autoComment);

        $validated = $this->validate([
            'autoWord' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            'autoPartOfSpeech' => ['required', 'string', Rule::in(self::PARTS_OF_SPEECH)],
            'autoTranslation' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            'autoComment' => ['nullable', 'string', 'max:600', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
        ]);

        $this->storeWord(
            $validated['autoWord'],
            $validated['autoPartOfSpeech'],
            $validated['autoTranslation'],
            $validated['autoComment'] ?? null,
        );
        $this->reset(['autoWord', 'autoPartOfSpeech', 'autoTranslation', 'autoComment']);
        $this->autoSuggestions = [];
        $this->autoTranslated = false;
        $this->autoTranslationError = '';
        $this->resetValidation();
        $this->formRenderKey++;
        $this->showCreateForm = true;
        $this->resetPage();
        $this->dispatch('reset-auto-add-word-form');
    }

    public function translateAutomatically(TranslationServiceInterface $translationService): void
    {
        $this->autoWord = $this->sanitizeTextInput($this->autoWord);

        $validated = $this->validate([
            'autoWord' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
        ]);

        $this->resetValidation(['autoTranslation', 'autoPartOfSpeech', 'autoComment']);
        $this->autoTranslationError = '';
        $this->autoSuggestions = [];
        $this->autoTranslated = false;
        $this->autoTranslation = '';

        $sourceLanguage = $this->sourceLanguageCode();
        if ($sourceLanguage === null) {
            $this->autoTranslationError = 'Translation is currently unavailable. Please switch to Enter manually.';
            $this->dispatch('auto-translation-unavailable');

            return;
        }

        try {
            $result = $translationService->translate(
                $validated['autoWord'],
                $sourceLanguage,
                self::TARGET_LANGUAGE,
            );
        } catch (ConnectionException|RequestException) {
            $this->autoTranslationError = 'Translation is currently unavailable. Please switch to Enter manually.';
            $this->dispatch('auto-translation-unavailable');

            return;
        }

        $this->autoSuggestions = $result->toArray();

        if ($this->autoSuggestions === []) {
            $this->autoTranslationError = 'Translation is currently unavailable. Please switch to Enter manually.';
            $this->dispatch('auto-translation-unavailable');

            return;
        }

        $this->autoTranslated = true;
        $this->autoTranslation = $this->autoSuggestions[0]['text'];
        $this->dispatch('auto-translation-ready', selectedTranslation: $this->autoTranslation);
    }

    public function selectAutoTranslationByIndex(int $index): void
    {
        if (! isset($this->autoSuggestions[$index])) {
            return;
        }

        $translation = trim((string) ($this->autoSuggestions[$index]['text'] ?? ''));
        if ($translation === '') {
            return;
        }

        $this->autoTranslation = $translation;
        $this->resetValidation('autoTranslation');
        $this->dispatch('auto-translation-selected', selectedTranslation: $translation);
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function applySearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
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
        $this->reset([
            'showCreateForm',
            'word',
            'partOfSpeech',
            'translation',
            'comment',
            'autoWord',
            'autoPartOfSpeech',
            'autoTranslation',
            'autoComment',
        ]);
        $this->autoSuggestions = [];
        $this->autoTranslated = false;
        $this->autoTranslationError = '';
        $this->resetValidation();
        $this->formRenderKey++;
        $this->dispatch('reset-auto-add-word-form');
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
            'noun' => 'Noun (&#1057;&#1091;&#1097;&#1077;&#1089;&#1090;&#1074;&#1080;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077;)',
            'verb' => 'Verb (&#1043;&#1083;&#1072;&#1075;&#1086;&#1083;)',
            'adjective' => 'Adjective (&#1055;&#1088;&#1080;&#1083;&#1072;&#1075;&#1072;&#1090;&#1077;&#1083;&#1100;&#1085;&#1086;&#1077;)',
            'adverb' => 'Adverb (&#1053;&#1072;&#1088;&#1077;&#1095;&#1080;&#1077;)',
            'pronoun' => 'Pronoun (&#1052;&#1077;&#1089;&#1090;&#1086;&#1080;&#1084;&#1077;&#1085;&#1080;&#1077;)',
            'preposition' => 'Preposition (&#1055;&#1088;&#1077;&#1076;&#1083;&#1086;&#1075;)',
            'conjunction' => 'Conjunction (&#1057;&#1086;&#1102;&#1079;)',
            'interjection' => 'Interjection (&#1052;&#1077;&#1078;&#1076;&#1086;&#1084;&#1077;&#1090;&#1080;&#1077;)',
            'stable_expression' => 'Stable expression (&#1059;&#1089;&#1090;&#1086;&#1081;&#1095;&#1080;&#1074;&#1086;&#1077; &#1074;&#1099;&#1088;&#1072;&#1078;&#1077;&#1085;&#1080;&#1077;)',
        ];
    }

    private function storeWord(
        string $wordValue,
        string $partOfSpeechValue,
        string $translationValue,
        ?string $commentValue,
    ): void {
        DB::transaction(function () use ($wordValue, $partOfSpeechValue, $translationValue, $commentValue): void {
            $word = Word::create([
                'word' => $wordValue,
                'part_of_speech' => $partOfSpeechValue,
                'translation' => $translationValue,
                'comment' => $commentValue,
            ]);

            $this->dictionary->words()->attach($word->id);
        });
    }

    private function sanitizeTextInput(?string $value): string
    {
        $normalized = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', (string) $value) ?? (string) $value;

        return trim($normalized);
    }

    private function sourceLanguageCode(): ?string
    {
        $language = trim((string) $this->dictionary->language);

        return self::DICTIONARY_LANGUAGE_CODES[$language] ?? null;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
