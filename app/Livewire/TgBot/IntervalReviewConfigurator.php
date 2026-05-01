<?php

namespace App\Livewire\TgBot;

use App\Data\Telegram\IntervalReviewPlanData;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Telegram\TelegramIntervalReviewPlanService;
use App\Services\Telegram\TelegramIntervalReviewSchedulePreviewService;
use App\Support\PartOfSpeechCatalog;
use Carbon\CarbonImmutable;
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
    public bool $hasPersistedPlan = false;
    public bool $showResetConfirmation = false;
    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';
    public string $planStatusCode = 'paused';
    public int $completedSessionsCount = 0;
    public ?string $nextSessionLabel = null;

    public function mount(string $timezone = 'Europe/Moscow'): void
    {
        $this->timezone = $timezone;
        $this->loadPersistedPlan(app(TelegramIntervalReviewPlanService::class));
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
            'planStatusLabel' => $this->resolvePlanStatusLabel(),
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
        $this->hideFeedback();
        $this->showResetConfirmation = false;
        $this->resetPreview();
    }

    public function updatedStartTime(): void
    {
        $this->hideFeedback();
        $this->showResetConfirmation = false;
        $this->resetPreview();
    }

    public function updatedEnabled(): void
    {
        $this->hideFeedback();
        $this->showResetConfirmation = false;

        if ($this->hasPersistedPlan) {
            /** @var User $user */
            $user = auth()->user();
            $plan = app(TelegramIntervalReviewPlanService::class)->toggleStatus($user, $this->enabled);

            if ($plan instanceof TelegramIntervalReviewPlan) {
                if ($plan->status === TelegramIntervalReviewPlan::STATUS_COMPLETED) {
                    $this->applyPlanState($plan);
                    $this->feedbackType = 'success';
                    $this->feedbackMessage = __('tg-bot.interval_review.messages.completed');

                    return;
                }

                $this->hasPersistedPlan = true;
                $this->planStatusCode = (string) $plan->status;
                $this->completedSessionsCount = (int) $plan->completed_sessions_count;
                $this->nextSessionLabel = $plan->sessions
                    ->first(fn ($session) => in_array($session->status, [
                        'scheduled',
                        'awaiting_start',
                        'in_progress',
                    ], true))
                    ?->scheduled_for
                    ?->setTimezone($plan->timezone)
                    ?->translatedFormat('d.m.Y H:i');
                $this->feedbackType = 'success';
                $this->feedbackMessage = $this->enabled
                    ? __('tg-bot.interval_review.messages.resumed')
                    : __('tg-bot.interval_review.messages.paused');

                return;
            }
        }

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

    public function applyPlan(TelegramIntervalReviewPlanService $planService): void
    {
        $this->resetErrorBag();
        $this->hideFeedback();
        $this->showResetConfirmation = false;

        if (count($this->selectedWords) === 0) {
            $this->addError('interval_review_words', __('tg-bot.interval_review.validation.words_required'));

            return;
        }

        if ($this->startTime === '') {
            $this->addError('interval_review_start_time', __('tg-bot.interval_review.validation.start_time_required'));

            return;
        }

        /** @var User $user */
        $user = auth()->user();

        $plan = $planService->save($user, $this->toPlanData());
        $this->applyPlanState($plan);
        $this->feedbackType = 'success';
        $this->feedbackMessage = __('tg-bot.interval_review.messages.saved');
    }

    public function buildPlanPreview(TelegramIntervalReviewSchedulePreviewService $previewService): void
    {
        $this->resetErrorBag();
        $this->hideFeedback();

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

    public function collapsePlanPreview(): void
    {
        $this->planPreviewVisible = false;
    }

    public function toggleWordSelection(string $source, int $dictionaryId, int $wordId): void
    {
        $this->hideFeedback();
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
        $this->hideFeedback();

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
        $this->hideFeedback();

        foreach ($this->modalWords()['items'] as $word) {
            unset($this->selectedWords[$word['selection_key']]);
        }

        $this->selectedWords = $this->selectedWords;
        $this->resetErrorBag('selection_limit');
        $this->resetPreview();
    }

    public function removeSelectedWord(string $selectionKey): void
    {
        $this->hideFeedback();
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

    public function confirmReset(): void
    {
        $this->showResetConfirmation = true;
        $this->hideFeedback();
    }

    public function cancelReset(): void
    {
        $this->showResetConfirmation = false;
    }

    public function resetPlan(TelegramIntervalReviewPlanService $planService): void
    {
        /** @var User $user */
        $user = auth()->user();

        $planService->reset($user);

        $this->enabled = false;
        $this->planStatusCode = 'paused';
        $this->completedSessionsCount = 0;
        $this->nextSessionLabel = null;
        $this->selectedLanguage = 'English';
        $this->startTime = '09:00';
        $this->selectedWords = [];
        $this->schedulePreview = [];
        $this->planPreviewVisible = false;
        $this->hasPersistedPlan = false;
        $this->showResetConfirmation = false;
        $this->modalOpen = false;
        $this->modalDictionaryId = null;
        $this->modalSource = 'user';
        $this->modalSearch = '';
        $this->modalPartOfSpeech = PartOfSpeechCatalog::ALL;
        $this->modalPage = 1;
        $this->feedbackType = 'success';
        $this->feedbackMessage = __('tg-bot.interval_review.messages.reset');
        $this->resetErrorBag();
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

    private function toPlanData(): IntervalReviewPlanData
    {
        return new IntervalReviewPlanData(
            enabled: $this->enabled,
            language: $this->selectedLanguage,
            startTime: $this->startTime,
            timezone: $this->timezone,
            selectedWords: array_values($this->selectedWords),
        );
    }

    private function loadPersistedPlan(TelegramIntervalReviewPlanService $planService): void
    {
        /** @var User $user */
        $user = auth()->user();
        $plan = $planService->loadForUser($user);

        if (! $plan instanceof TelegramIntervalReviewPlan) {
            return;
        }

        $this->applyPlanState($plan);
    }

    private function applyPlanState(TelegramIntervalReviewPlan $plan): void
    {
        $this->enabled = $plan->status === TelegramIntervalReviewPlan::STATUS_ACTIVE;
        $this->planStatusCode = (string) $plan->status;
        $this->completedSessionsCount = (int) $plan->completed_sessions_count;
        $this->selectedLanguage = (string) $plan->language;
        $this->startTime = substr((string) $plan->start_time, 0, 5);
        $this->timezone = (string) $plan->timezone;
        $this->selectedWords = $plan->words
            ->map(function ($word): array {
                $selectionKey = "{$word->source_type}:{$word->source_dictionary_id}:{$word->source_word_id}";

                return [
                    'selection_key' => $selectionKey,
                    'source' => (string) $word->source_type,
                    'source_label' => $word->source_type === 'user'
                        ? __('tg-bot.interval_review.selected_words.source_user')
                        : __('tg-bot.interval_review.selected_words.source_ready'),
                    'dictionary_id' => (int) $word->source_dictionary_id,
                    'dictionary_name' => (string) $word->dictionary_name,
                    'language' => (string) $word->language,
                    'word_id' => (int) $word->source_word_id,
                    'word' => (string) $word->word,
                    'translation' => (string) $word->translation,
                    'part_of_speech' => $word->part_of_speech !== null && trim((string) $word->part_of_speech) !== ''
                        ? (string) $word->part_of_speech
                        : null,
                    'comment' => $word->comment !== null && trim((string) $word->comment) !== ''
                        ? (string) $word->comment
                        : null,
                ];
            })
            ->keyBy('selection_key')
            ->all();
        $this->schedulePreview = $plan->sessions
            ->map(fn ($session): array => [
                'session_number' => $session->session_number,
                'label' => __('tg-bot.interval_review.preview.session_label', ['number' => $session->session_number]),
                'scheduled_at_local' => $session->scheduled_for
                    ->setTimezone($plan->timezone)
                    ->translatedFormat('d.m.Y H:i'),
                'scheduled_at_iso' => CarbonImmutable::parse($session->scheduled_for)->toIso8601String(),
            ])
            ->values()
            ->all();
        $this->nextSessionLabel = $plan->sessions
            ->first(fn ($session) => in_array($session->status, [
                'scheduled',
                'awaiting_start',
                'in_progress',
            ], true))
            ?->scheduled_for
            ?->setTimezone($plan->timezone)
            ?->translatedFormat('d.m.Y H:i');
        $this->planPreviewVisible = $this->schedulePreview !== [];
        $this->hasPersistedPlan = true;
        $this->showResetConfirmation = false;
    }

    private function resolvePlanStatusLabel(): string
    {
        return match ($this->planStatusCode) {
            TelegramIntervalReviewPlan::STATUS_ACTIVE => __('tg-bot.interval_review.plan_status.active'),
            TelegramIntervalReviewPlan::STATUS_COMPLETED => __('tg-bot.interval_review.plan_status.completed'),
            default => __('tg-bot.interval_review.plan_status.paused'),
        };
    }

    private function hideFeedback(): void
    {
        $this->feedbackMessage = null;
        $this->feedbackType = 'success';
    }
}
