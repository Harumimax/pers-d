<?php

namespace App\Services\DictionarySubscriptions;

use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Database\Eloquent\Builder;

class DictionaryAccessService
{
    public function canManage(?User $user, UserDictionary $dictionary): bool
    {
        return $user !== null && (int) $dictionary->user_id === (int) $user->id;
    }

    public function canView(?User $user, UserDictionary $dictionary): bool
    {
        if ($this->canManage($user, $dictionary)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $dictionary->subscriptions()
            ->where('subscriber_user_id', $user->id)
            ->exists();
    }

    public function accessibleDictionariesQuery(User $user): Builder
    {
        return UserDictionary::query()
            ->select('user_dictionaries.*')
            ->leftJoin('dictionary_subscriptions as access_subscriptions', function ($join) use ($user): void {
                $join->on('access_subscriptions.user_dictionary_id', '=', 'user_dictionaries.id')
                    ->where('access_subscriptions.subscriber_user_id', '=', $user->id);
            })
            ->where(function ($builder) use ($user): void {
                $builder->where('user_dictionaries.user_id', $user->id)
                    ->orWhereNotNull('access_subscriptions.id');
            })
            ->distinct();
    }

    /**
     * @return array<int, int>
     */
    public function accessibleDictionaryIds(User $user): array
    {
        return $this->accessibleDictionariesQuery($user)
            ->pluck('user_dictionaries.id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    public function findAccessibleDictionary(User $user, int $dictionaryId): ?UserDictionary
    {
        if ($dictionaryId <= 0) {
            return null;
        }

        return $this->accessibleDictionariesQuery($user)
            ->where('user_dictionaries.id', $dictionaryId)
            ->first();
    }
}
