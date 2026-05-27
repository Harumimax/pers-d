<?php

namespace App\Livewire\Dictionaries;

use App\Models\User;
use App\Models\Word;
use App\Services\Navigation\HeaderNavigationService;
use App\Support\PartOfSpeechCatalog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    private const CONTROL_CHARACTER_PATTERN = '/[\p{Cc}\p{Cf}]/u';
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';

    public string $name = '';
    public string $language = '';
    public string $searchQuery = '';
    public bool $searchSubmitted = false;
    public bool $showCreateForm = false;
    public int $formRenderKey = 0;
    public ?int $pendingDeleteDictionaryId = null;
    public string $pendingDeleteDictionaryLabel = '';
    public ?int $editingDictionaryId = null;
    public string $editingDictionaryName = '';

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

    public function searchWords(): void
    {
        $this->searchQuery = $this->sanitizeTextInput($this->searchQuery);
        $this->resetValidation('searchQuery');

        if ($this->searchQuery === '') {
            $this->searchSubmitted = false;

            return;
        }

        $validator = Validator::make(
            ['searchQuery' => $this->searchQuery],
            [
                'searchQuery' => ['required', 'string', 'max:255', 'not_regex:'.self::CONTROL_CHARACTER_PATTERN],
            ],
            [],
            [
                'searchQuery' => __('dictionaries.index.search.placeholder'),
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->get('searchQuery') as $message) {
                $this->addError('searchQuery', $message);
            }

            return;
        }

        $this->searchSubmitted = true;
    }

    public function clearSearch(): void
    {
        $this->reset(['searchQuery', 'searchSubmitted']);
        $this->resetValidation('searchQuery');
    }

    public function startEditingDictionary(int $dictionaryId): void
    {
        $user = $this->currentUser();

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        $this->editingDictionaryId = $dictionary->id;
        $this->editingDictionaryName = $dictionary->name;
        $this->resetValidation('editingDictionaryName');
    }

    public function cancelEditingDictionary(): void
    {
        $this->editingDictionaryId = null;
        $this->editingDictionaryName = '';
        $this->resetValidation('editingDictionaryName');
    }

    public function updateEditingDictionaryName(): void
    {
        $user = $this->currentUser();

        $dictionaryId = $this->editingDictionaryId;
        abort_if($dictionaryId === null, 404);

        $dictionary = $user->dictionaries()->find($dictionaryId);

        abort_if($dictionary === null, 403);

        $this->editingDictionaryName = trim($this->editingDictionaryName);

        $this->resetValidation('editingDictionaryName');

        $validator = Validator::make(
            ['editingDictionaryName' => $this->editingDictionaryName],
            [
                'editingDictionaryName' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('user_dictionaries', 'name')
                        ->where(fn ($query) => $query->where('user_id', $user->id))
                        ->ignore($dictionary->id),
                ],
            ],
            [],
            $this->validationAttributes()
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->get('editingDictionaryName') as $message) {
                $this->addError('editingDictionaryName', $message);
            }

            return;
        }

        /** @var array{editingDictionaryName: string} $validated */
        $validated = $validator->validated();

        $dictionary->update([
            'name' => $validated['editingDictionaryName'],
        ]);

        $this->cancelEditingDictionary();
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

        /** @var EloquentCollection<int, \App\Models\UserDictionary> $dictionaries */
        $dictionaries = $user->dictionaries()
            ->withCount('words')
            ->orderByDesc('created_at')
            ->get();
        $searchResults = $this->searchResults($user);

        $headerNavigation = app(HeaderNavigationService::class)->forUser($user);

        return view('livewire.dictionaries.index', [
            'dictionaries' => $dictionaries,
            'searchResults' => $searchResults,
            'partOfSpeechDisplayMap' => PartOfSpeechCatalog::labels(),
        ])->layout('layouts.dictionaries', $headerNavigation);
    }

    /**
     * @return Collection<int, object{
     *     dictionary_id:int,
     *     dictionary_name:string,
     *     dictionary_language:?string,
     *     word_id:int,
     *     word:string,
     *     translation:string,
     *     comment:?string,
     *     part_of_speech:?string,
     *     remainder_had_mistake:bool,
     *     attached_at:?CarbonInterface
     * }>
     */
    private function searchResults(User $user): Collection
    {
        if (! $this->searchSubmitted) {
            return collect();
        }

        $searchTerm = trim($this->searchQuery);

        if ($searchTerm === '') {
            return collect();
        }

        $normalizedSearchTerm = mb_strtolower($searchTerm);

        return Word::query()
            ->join('user_dictionary_word', 'user_dictionary_word.word_id', '=', 'words.id')
            ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
            ->where('user_dictionaries.user_id', $user->id)
            ->where(function ($query) use ($normalizedSearchTerm): void {
                $query->whereRaw('LOWER(words.word) LIKE ?', ['%'.$normalizedSearchTerm.'%'])
                    ->orWhereRaw('LOWER(words.translation) LIKE ?', ['%'.$normalizedSearchTerm.'%']);
            })
            ->orderBy('words.word')
            ->orderBy('user_dictionaries.name')
            ->get([
                'user_dictionaries.id as dictionary_id',
                'user_dictionaries.name as dictionary_name',
                'user_dictionaries.language as dictionary_language',
                'words.id as word_id',
                'words.word',
                'words.translation',
                'words.comment',
                'words.part_of_speech',
                'words.remainder_had_mistake',
                'user_dictionary_word.created_at as attached_at',
            ])
            ->map(function (Word $result): object {
                return (object) [
                    'dictionary_id' => (int) $result->getAttribute('dictionary_id'),
                    'dictionary_name' => (string) $result->getAttribute('dictionary_name'),
                    'dictionary_language' => $result->getAttribute('dictionary_language'),
                    'word_id' => (int) $result->getAttribute('word_id'),
                    'word' => (string) $result->getAttribute('word'),
                    'translation' => (string) $result->getAttribute('translation'),
                    'comment' => $result->getAttribute('comment'),
                    'part_of_speech' => $result->getAttribute('part_of_speech'),
                    'remainder_had_mistake' => (bool) $result->getAttribute('remainder_had_mistake'),
                    'attached_at' => $result->getAttribute('attached_at') !== null
                        ? Carbon::parse((string) $result->getAttribute('attached_at'))
                        : null,
                ];
            });
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'editingDictionaryName' => __('dictionaries.index.fields.name'),
        ];
    }

    private function sanitizeTextInput(?string $value): string
    {
        $normalized = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', (string) $value) ?? (string) $value;

        return trim($normalized);
    }
}
