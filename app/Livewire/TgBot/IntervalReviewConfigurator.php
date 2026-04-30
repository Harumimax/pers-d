<?php

namespace App\Livewire\TgBot;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Telegram\TelegramIntervalReviewSchedulePreviewService;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class IntervalReviewConfigurator extends Component
{
    private const MAX_SELECTED_WORDS = 20;
    private const WORDS_PER_PAGE = 20;

    public bool $enabled = false;
    public string $selectedLanguage = 'English';
    public string $startTime = '09:00';
    public string $timezone = 'Europe/Moscow';
    public bool $modalOpen = false;
    public string $modalSource = 'user';
    public ?int $modalDictionaryId = null;
    public string $modalSearch = '';
    public string $modalPartOfSpeech = PartOfSpeechCatalog::ALL;
    public int $modalPage = 1;
    public array $selectedWords = [];
    public array $schedulePreview = [];
    public bool $planPreviewVisible = false;

    public function mount(string $timezone = 'Europe/Moscow'): void
    {
        $this->timezone = $timezone;
    }

    public function render(): View
    {
        return view('livewire.tg-bot.interval-review-configurator', [
            'languageOptions' => $this->languageOptions(),
            'userDictionaries' => $this->userDictionaries(),
            'readyDictionaries' => $this->readyDictionaries(),
            'partOfSpeechOptions' => PartOfSpeechCatalog::dictionaryFilterLabels(),
            'modalDictionary' => $this->modalDictionary(),
            'modalWords' => $this->modalWords(),
            'selectedWordGroups' => $this->selectedWordGroups(),
            'selectedWordsCount' => count($this->selectedWords),
            'firstSessionPreviewWords' => array_values($this->selectedWords),
        ]);
    }

    public function openDictionary(string $source, int $dictionaryId): void
    {
        if (! in_array($source, ['user', 'ready'], true)) {
            return;
        }

        $this->modalOpen = true;
        $this->modalSource = $source;
        $this->modalDictionaryId = $dictionaryId;
        $this->modalSearch = '';
        $this->modalPartOfSpeech = PartOfSpeechCatalog::ALL;
        $this->modalPage = 1;
        $this->resetErrorBag('selection_limit');
    }

    public function closeDictionary(): void
    {
        $this->modalOpen = false;
        $this->modalDictionaryId = null;
        $this->modalSearch = '';
        $this->modalPartOfSpeech = PartOfSpeechCatalog::ALL;
        $this->modalPage = 1;
    }

    public function updatedSelectedLanguage(): void
    {
        $this->selectedWords = collect($this->selectedWords)
            ->filter(fn (array $word): bool => ($word['language'] ?? null) === $this->selectedLanguage)
            ->all();

        $this->closeDictionary();
        $this->resetPreview();
    }

    public function updatedStartTime(): void
    {
        $this->resetPreview();
    }

    public function updatedEnabled(): void
    {
        $this->resetPreview();
    }

    public function updatedModalSearch(): void
    {
        $this->modalPage = 1;
    }

    public function updatedModalPartOfSpeech(): void
    {
        $this->modalPage = 1;
    }

    public function buildPlanPreview(TelegramIntervalReviewSchedulePreviewService $previewService): void
    {
        $this->resetErrorBag();

        if (count($this->selectedWords) === 0) {
            $this->addError('interval_review_words', __('tg-bot.interval_review.validation.words_required'));

            return;
        }

        if ($this->startTime === '') {
            $this->addError('interval_review_start_time', __('tg-bot.interval_review.validation.start_time_required'));

            return;
        }

        $this->schedulePreview = $previewService->build($this->timezone, $this->startTime);
        $this->planPreviewVisible = true;
    }

    public function toggleWordSelection(string $source, int $dictionaryId, int $wordId): void
    {
        $wordData = $this->findWordData($source, $dictionaryId, $wordId);

        if ($wordData === null) {
            return;
        }

        $key = $wordData['selection_key'];

        if (array_key_exists($key, $this->selectedWords)) {
            unset($this->selectedWords[$key]);
            $this->selectedWords = $this->selectedWords;
            $this->resetErrorBag('selection_limit');
            $this->resetPreview();

            return;
        }

        if (count($this->selectedWords) >= self::MAX_SELECTED_WORDS) {
            $this->addError('selection_limit', __('tg-bot.interval_review.validation.selection_limit', ['count' => self::MAX_SELECTED_WORDS]));

            return;
        }

        $this->selectedWords[$key] = $wordData;
        $this->resetErrorBag('selection_limit');
        $this->resetPreview();
    }

    public function selectAllVisibleWords(): void
    {
        foreach ($this->modalWords()['items'] as $word) {
            if (count($this->selectedWords) >= self::MAX_SELECTED_WORDS) {
                $this->addError('selection_limit', __('tg-bot.interval_review.validation.selection_limit', ['count' => self::MAX_SELECTED_WORDS]));

                break;
            }

            $this->selectedWords[$word['selection_key']] = $word;
        }

        $this->selectedWords = $this->selectedWords;
        $this->resetPreview();
    }

    public function clearVisibleWordsSelection(): void
    {
        foreach ($this->modalWords()['items'] as $word) {
            unset($this->selectedWords[$word['selection_key']]);
        }

        $this->selectedWords = $this->selectedWords;
        $this->resetErrorBag('selection_limit');
        $this->resetPreview();
    }

    public function removeSelectedWord(string $selectionKey): void
    {
        unset($this->selectedWords[$selectionKey]);
        $this->selectedWords = $this->selectedWords;
        $this->resetErrorBag('selection_limit');
        $this->resetPreview();
    }

    public function gotoModalPage(int $page): void
    {
        $maxPage = $this->modalWords()['last_page'];
        $this->modalPage = max(1, min($page, $maxPage));
    }

    public function previousModalPage(): void
    {
        $this->gotoModalPage($this->modalPage - 1);
    }

    public function nextModalPage(): void
    {
        $this->gotoModalPage($this->modalPage + 1);
    }

    public function isWordSelected(string $selectionKey): bool
    {
        return array_key_exists($selectionKey, $this->selectedWords);
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function languageOptions(): array
    {
        return [
            ['value' => 'English', 'label' => __('dictionaries.index.languages.english')],
            ['value' => 'Spanish', 'label' => __('dictionaries.index.languages.spanish')],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, UserDictionary>
     */
    private function userDictionaries()
    {
        /** @var User $user */
        $user = auth()->user();

        return UserDictionary::query()
            ->where('user_id', $user->id)
            ->where('language', $this->selectedLanguage)
            ->withCount('words')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ReadyDictionary>
     */
    private function readyDictionaries()
    {
        return ReadyDictionary::query()
            ->where('language', $this->selectedLanguage)
            ->withCount('words')
            ->orderBy('name')
            ->get();
    }

    private function modalDictionary(): UserDictionary|ReadyDictionary|null
    {
        if (! $this->modalOpen || $this->modalDictionaryId === null) {
            return null;
        }

        if ($this->modalSource === 'user') {
            /** @var User $user */
            $user = auth()->user();

            return UserDictionary::query()
                ->where('user_id', $user->id)
                ->where('language', $this->selectedLanguage)
                ->find($this->modalDictionaryId);
        }

        return ReadyDictionary::query()
            ->where('language', $this->selectedLanguage)
            ->find($this->modalDictionaryId);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>,current_page:int,last_page:int,total:int,from:int,to:int}
     */
    private function modalWords(): array
    {
        $dictionary = $this->modalDictionary();

        if ($dictionary === null) {
            return [
                'items' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
                'from' => 0,
                'to' => 0,
            ];
        }

        $search = mb_strtolower(trim($this->modalSearch));
        $offset = ($this->modalPage - 1) * self::WORDS_PER_PAGE;

        if ($dictionary instanceof UserDictionary) {
            $query = $dictionary->words()->select('words.*');
        } else {
            $query = $dictionary->words()->select('ready_dictionary_words.*');
        }

        if ($this->modalPartOfSpeech !== PartOfSpeechCatalog::ALL) {
            $query->where('part_of_speech', $this->modalPartOfSpeech);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->whereRaw('LOWER(word) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(translation) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(COALESCE(comment, \'\')) LIKE ?', ['%'.$search.'%']);
            });
        }

        $paginator = $query
            ->orderBy('word')
            ->paginate(self::WORDS_PER_PAGE, ['*'], 'modalPage', $this->modalPage);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($word) => $this->normalizeWord($word, $dictionary))
                ->values()
                ->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findWordData(string $source, int $dictionaryId, int $wordId): ?array
    {
        if ($source === 'user') {
            /** @var User $user */
            $user = auth()->user();

            $dictionary = UserDictionary::query()
                ->where('user_id', $user->id)
                ->where('language', $this->selectedLanguage)
                ->find($dictionaryId);

            if (! $dictionary instanceof UserDictionary) {
                return null;
            }

            $word = $dictionary->words()->select('words.*')->find($wordId);

            return $word instanceof Word ? $this->normalizeWord($word, $dictionary) : null;
        }

        $dictionary = ReadyDictionary::query()
            ->where('language', $this->selectedLanguage)
            ->find($dictionaryId);

        if (! $dictionary instanceof ReadyDictionary) {
            return null;
        }

        $word = $dictionary->words()->select('ready_dictionary_words.*')->find($wordId);

        return $word instanceof ReadyDictionaryWord ? $this->normalizeWord($word, $dictionary) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeWord(Word|ReadyDictionaryWord $word, UserDictionary|ReadyDictionary $dictionary): array
    {
        $source = $dictionary instanceof UserDictionary ? 'user' : 'ready';
        $selectionKey = "{$source}:{$dictionary->id}:{$word->id}";

        return [
            'selection_key' => $selectionKey,
            'source' => $source,
            'source_label' => $source === 'user'
                ? __('tg-bot.interval_review.selected_words.source_user')
                : __('tg-bot.interval_review.selected_words.source_ready'),
            'dictionary_id' => $dictionary->id,
            'dictionary_name' => $dictionary->name,
            'language' => (string) $dictionary->language,
            'word_id' => $word->id,
            'word' => (string) $word->word,
            'translation' => (string) $word->translation,
            'part_of_speech' => $word->part_of_speech !== null
                ? PartOfSpeechCatalog::label((string) $word->part_of_speech)
                : null,
            'comment' => $word->comment !== null && trim((string) $word->comment) !== ''
                ? (string) $word->comment
                : null,
        ];
    }

    /**
     * @return array<string, array<string, array<int, array<string,mixed>>>>
     */
    private function selectedWordGroups(): array
    {
        return collect($this->selectedWords)
            ->groupBy('source_label')
            ->map(fn ($sourceWords) => $sourceWords->groupBy('dictionary_name')->map(fn ($dictionaryWords) => $dictionaryWords->values()->all())->all())
            ->all();
    }

    private function resetPreview(): void
    {
        $this->planPreviewVisible = false;
        $this->schedulePreview = [];
    }
}
