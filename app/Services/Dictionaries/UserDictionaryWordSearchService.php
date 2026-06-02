<?php

namespace App\Services\Dictionaries;

use App\Models\User;
use App\Models\Word;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UserDictionaryWordSearchService
{
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
    public function search(User $user, string $query): Collection
    {
        $searchTerm = trim($query);

        if ($searchTerm === '') {
            return collect();
        }

        $normalizedSearchTerm = mb_strtolower($searchTerm);

        return Word::query()
            ->withProgressForUser($user)
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
            ->where(function ($builder) use ($normalizedSearchTerm): void {
                $builder->whereRaw('LOWER(words.word) LIKE ?', ['%'.$normalizedSearchTerm.'%'])
                    ->orWhereRaw('LOWER(words.translation) LIKE ?', ['%'.$normalizedSearchTerm.'%']);
            })
            ->orderBy('words.word')
            ->orderBy('user_dictionaries.name')
            ->addSelect([
                'user_dictionaries.id as dictionary_id',
                'user_dictionaries.name as dictionary_name',
                'user_dictionaries.language as dictionary_language',
                'words.id as word_id',
                'words.word',
                'words.translation',
                'words.comment',
                'words.part_of_speech',
                'user_dictionary_word.created_at as attached_at',
            ])
            ->distinct()
            ->get()
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
                    'remainder_had_mistake' => $result->remainder_had_mistake,
                    'attached_at' => $result->getAttribute('attached_at') !== null
                        ? Carbon::parse((string) $result->getAttribute('attached_at'))
                        : null,
                ];
            });
    }
}
