<?php

namespace App\Livewire\ReadyDictionaries;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Navigation\HeaderNavigationService;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Show extends Component
{
    use WithPagination;

    private const PART_OF_SPEECH_FILTER_ALL = PartOfSpeechCatalog::ALL;
    private const SORT_NEWEST = 'newest';
    private const SORT_OLDEST = 'oldest';
    private const SORT_A_TO_Z = 'a-z';

    public ReadyDictionary $readyDictionary;
    public string $partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
    public string $search = '';
    public string $sort = self::SORT_NEWEST;
    public ?string $transferBannerType = null;
    public ?string $transferBannerMessage = null;

    public function mount(ReadyDictionary $readyDictionary): void
    {
        abort_unless(Auth::check(), 401);

        $this->readyDictionary = $readyDictionary;
    }

    public function render(): View
    {
        $user = $this->currentUser();
        $totalWordsCount = $this->readyDictionary->words()->count();
        $wordsQuery = $this->readyDictionary->words();
        $searchTerm = trim($this->search);
        $normalizedSearchTerm = mb_strtolower($searchTerm);

        $headerNavigation = app(HeaderNavigationService::class)->forUser($user);
        $userDictionaries = $headerNavigation['headerDictionaries'];

        if ($this->partOfSpeechFilter !== self::PART_OF_SPEECH_FILTER_ALL) {
            $wordsQuery->where('part_of_speech', $this->partOfSpeechFilter);
        }

        if ($searchTerm !== '') {
            $wordsQuery->where(function ($query) use ($normalizedSearchTerm): void {
                $query->whereRaw('LOWER(word) LIKE ?', ['%'.$normalizedSearchTerm.'%'])
                    ->orWhereRaw('LOWER(translation) LIKE ?', ['%'.$normalizedSearchTerm.'%']);
            });
        }

        if ($this->sort === self::SORT_OLDEST) {
            $wordsQuery->orderBy('created_at');
        } elseif ($this->sort === self::SORT_A_TO_Z) {
            $wordsQuery->orderBy('word');
        } else {
            $wordsQuery->orderByDesc('created_at');
        }

        return view('livewire.ready-dictionaries.show', [
            'words' => $wordsQuery->paginate(20),
            'totalWordsCount' => $totalWordsCount,
            'partOfSpeechFilterOptions' => PartOfSpeechCatalog::dictionaryFilterLabels(),
            'partOfSpeechDisplayMap' => PartOfSpeechCatalog::labels(),
            'userDictionaries' => $userDictionaries,
        ])->layout('layouts.dictionaries', $headerNavigation);
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
            ...PartOfSpeechCatalog::values(),
        ];

        if (! in_array($value, $allowedValues, true)) {
            $this->partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
        }

        $this->resetPage();
    }

    public function transferWordToDictionary(int $readyDictionaryWordId, int $userDictionaryId): void
    {
        $this->resetTransferBanner();

        $user = $this->currentUser();
        $userDictionary = $user->dictionaries()
            ->whereKey($userDictionaryId)
            ->first();
        $readyDictionaryWord = $this->readyDictionary->words()
            ->whereKey($readyDictionaryWordId)
            ->first();

        if (! $userDictionary instanceof UserDictionary || ! $readyDictionaryWord instanceof ReadyDictionaryWord) {
            $this->showTransferError();

            return;
        }

        try {
            DB::transaction(function () use ($readyDictionaryWord, $userDictionary): void {
                $word = Word::create([
                    'word' => $readyDictionaryWord->word,
                    'part_of_speech' => $readyDictionaryWord->part_of_speech,
                    'translation' => $readyDictionaryWord->translation,
                    'comment' => $readyDictionaryWord->comment,
                ]);

                $userDictionary->words()->attach($word->id);
            });
        } catch (Throwable) {
            $this->showTransferError();

            return;
        }

        $this->transferBannerType = 'success';
        $this->transferBannerMessage = __('ready_dictionaries.show.transfer.success', [
            'word' => $readyDictionaryWord->word,
            'dictionary' => $userDictionary->name,
        ]);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function resetTransferBanner(): void
    {
        $this->transferBannerType = null;
        $this->transferBannerMessage = null;
    }

    private function showTransferError(): void
    {
        $this->transferBannerType = 'error';
        $this->transferBannerMessage = __('ready_dictionaries.show.transfer.error');
    }
}
