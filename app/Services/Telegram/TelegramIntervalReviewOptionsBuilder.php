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
     * @return Collection<int, array{answer:string,part_of_speech:?string}>
     */
    private function buildAnswerPool(User $user, string $language): Collection
    {
        $userAnswers = Word::query()
            ->select('words.translation', 'words.part_of_speech')
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
            ->get()
            ->map(static fn (Word $word): array => [
                'answer' => trim((string) $word->translation),
                'part_of_speech' => $word->part_of_speech !== null && trim((string) $word->part_of_speech) !== ''
                    ? (string) $word->part_of_speech
                    : null,
            ]);

        $readyAnswers = ReadyDictionaryWord::query()
            ->select('ready_dictionary_words.translation', 'ready_dictionary_words.part_of_speech')
            ->join('ready_dictionaries', 'ready_dictionaries.id', '=', 'ready_dictionary_words.ready_dictionary_id')
            ->where('ready_dictionaries.language', $language)
            ->get()
            ->map(static fn (ReadyDictionaryWord $word): array => [
                'answer' => trim((string) $word->translation),
                'part_of_speech' => $word->part_of_speech !== null && trim((string) $word->part_of_speech) !== ''
                    ? (string) $word->part_of_speech
                    : null,
            ]);

        return $userAnswers
            ->merge($readyAnswers)
            ->filter(static fn (array $answer): bool => $answer['answer'] !== '')
            ->values();
    }
}
