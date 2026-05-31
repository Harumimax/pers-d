<?php

namespace App\Services\Telegram;

use App\Models\ReadyDictionaryWord;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\User;
use App\Models\Word;
use App\Services\Remainder\ChoiceOptionsBuilder;
use Illuminate\Support\Collection;

class TelegramIntervalReviewOptionsBuilder
{
    public function __construct(
        private readonly ChoiceOptionsBuilder $choiceOptionsBuilder,
    ) {
    }

    /**
     * @param Collection<int, array<string, mixed>> $itemPayloads
     * @return Collection<int, array<string, mixed>>
     */
    public function build(User $user, TelegramIntervalReviewPlan $plan, Collection $itemPayloads): Collection
    {
        $answerPool = $this->buildAnswerPool($user, (string) $plan->language);
        $result = $this->choiceOptionsBuilder->build($itemPayloads, $answerPool);

        return $result['items'];
    }

    /**
     * @return Collection<int, string>
     */
    private function buildAnswerPool(User $user, string $language): Collection
    {
        $userAnswers = Word::query()
            ->select('words.translation')
            ->join('user_dictionary_word', 'user_dictionary_word.word_id', '=', 'words.id')
            ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
            ->leftJoin('dictionary_subscriptions', function ($join) use ($user): void {
                $join->on('dictionary_subscriptions.user_dictionary_id', '=', 'user_dictionaries.id')
                    ->where('dictionary_subscriptions.subscriber_user_id', '=', $user->id);
            })
            ->where(function ($builder) use ($user): void {
                $builder->where('user_dictionaries.user_id', $user->id)
                    ->orWhereNotNull('dictionary_subscriptions.id');
            })
            ->where('user_dictionaries.language', $language)
            ->distinct()
            ->pluck('words.translation');

        $readyAnswers = ReadyDictionaryWord::query()
            ->select('ready_dictionary_words.translation')
            ->join('ready_dictionaries', 'ready_dictionaries.id', '=', 'ready_dictionary_words.ready_dictionary_id')
            ->where('ready_dictionaries.language', $language)
            ->pluck('ready_dictionary_words.translation');

        return $userAnswers
            ->merge($readyAnswers)
            ->map(static fn ($answer): string => trim((string) $answer))
            ->filter()
            ->values();
    }
}
