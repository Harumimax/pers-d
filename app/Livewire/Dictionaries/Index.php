<?php

namespace App\Livewire\Dictionaries;

use App\Models\User;
use App\Models\Word;
use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

        /** @var Collection<int, \App\Models\UserDictionary> $dictionaries */
        $dictionaries = $user->dictionaries()
            ->withCount('words')
            ->orderByDesc('created_at')
            ->get();

        $headerNavigation = app(HeaderNavigationService::class)->forUser($user);

        return view('livewire.dictionaries.index', [
            'dictionaries' => $dictionaries,
        ])->layout('layouts.dictionaries', $headerNavigation);
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
}
