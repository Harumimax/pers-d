<?php

namespace App\Livewire\Dictionaries;

use App\Models\FavoriteWord;
use App\Models\User;
use App\Services\Favorites\FavoriteWordsService;
use App\Services\Navigation\HeaderNavigationService;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Favorites extends Component
{
    use WithPagination;

    private const PART_OF_SPEECH_FILTER_ALL = PartOfSpeechCatalog::ALL;
    private const SORT_NEWEST = 'newest';
    private const SORT_OLDEST = 'oldest';
    private const SORT_A_TO_Z = 'a-z';

    public string $partOfSpeechFilter = self::PART_OF_SPEECH_FILTER_ALL;
    public string $search = '';
    public string $sort = self::SORT_NEWEST;

    public function render(FavoriteWordsService $favoriteWordsService): View
    {
        $user = $this->currentUser();
        $searchTerm = trim($this->search);
        $normalizedSearchTerm = mb_strtolower($searchTerm);
        $itemsQuery = $favoriteWordsService->favoritesPageQuery($user);
        $totalWordsCount = $favoriteWordsService->countForUser($user);
        $headerNavigation = app(HeaderNavigationService::class)->forUser($user);

        if ($this->partOfSpeechFilter !== self::PART_OF_SPEECH_FILTER_ALL) {
            $itemsQuery->whereRaw(
                'COALESCE(favorite_source_words.part_of_speech, favorite_ready_words.part_of_speech) = ?',
                [$this->partOfSpeechFilter]
            );
        }

        if ($searchTerm !== '') {
            $itemsQuery->where(function ($query) use ($normalizedSearchTerm): void {
                $query->whereRaw(
                    'LOWER(COALESCE(favorite_source_words.word, favorite_ready_words.word)) LIKE ?',
                    ['%'.$normalizedSearchTerm.'%']
                )->orWhereRaw(
                    'LOWER(COALESCE(favorite_source_words.translation, favorite_ready_words.translation)) LIKE ?',
                    ['%'.$normalizedSearchTerm.'%']
                );
            });
        }

        if ($this->sort === self::SORT_OLDEST) {
            $itemsQuery->orderBy('favorite_words.created_at');
        } elseif ($this->sort === self::SORT_A_TO_Z) {
            $itemsQuery->orderByRaw('COALESCE(favorite_source_words.word, favorite_ready_words.word)');
        } else {
            $itemsQuery->orderByDesc('favorite_words.created_at');
        }

        return view('livewire.dictionaries.favorites', [
            'favoriteWords' => $itemsQuery->paginate(20),
            'totalWordsCount' => $totalWordsCount,
            'partOfSpeechFilterOptions' => PartOfSpeechCatalog::dictionaryFilterLabels(),
            'partOfSpeechDisplayMap' => PartOfSpeechCatalog::labels(),
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

    public function removeFavorite(int $favoriteId, FavoriteWordsService $favoriteWordsService): void
    {
        $favorite = $favoriteWordsService->queryForUser($this->currentUser())
            ->where('favorite_words.id', $favoriteId)
            ->first();

        abort_if($favorite === null, 404);

        $favorite->delete();

        $currentPage = $this->getPage();
        $totalAfterDelete = $favoriteWordsService->countForUser($this->currentUser());
        $maxPage = max(1, (int) ceil($totalAfterDelete / 20));

        if ($currentPage > $maxPage) {
            $this->setPage($maxPage);
        }
    }

    public function sourceDictionaryRoute(string $sourceDictionaryType, int $sourceDictionaryId): string
    {
        return $sourceDictionaryType === FavoriteWord::SOURCE_DICTIONARY_READY
            ? route('ready-dictionaries.show', $sourceDictionaryId)
            : route('dictionaries.show', $sourceDictionaryId);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
