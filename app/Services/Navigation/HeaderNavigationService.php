<?php

namespace App\Services\Navigation;

use App\Models\ReadyDictionary;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class HeaderNavigationService
{
    /**
     * @return array{
     *     headerDictionaries: Collection<int, UserDictionary>,
     *     headerReadyDictionaries: Collection<int, ReadyDictionary>
     * }
     */
    public function forUser(?User $user): array
    {
        return [
            'headerDictionaries' => $this->userDictionaries($user),
            'headerReadyDictionaries' => $this->readyDictionaries(),
        ];
    }

    /**
     * @return Collection<int, UserDictionary>
     */
    private function userDictionaries(?User $user): Collection
    {
        if ($user === null) {
            return new Collection();
        }

        /** @var SupportCollection<int, UserDictionary> $dictionaries */
        $dictionaries = $user->ownedDictionaries()
            ->select(['id', 'name', 'created_at'])
            ->get()
            ->concat(
                $user->subscribedDictionaries()
                    ->select(['user_dictionaries.id', 'user_dictionaries.name', 'user_dictionaries.created_at'])
                    ->get()
            )
            ->unique('id')
            ->sortByDesc(
                fn (UserDictionary $dictionary): int => optional($dictionary->created_at)?->getTimestamp() ?? 0
            )
            ->values();

        return new Collection($dictionaries->all());
    }

    /**
     * @return Collection<int, ReadyDictionary>
     */
    private function readyDictionaries(): Collection
    {
        return ReadyDictionary::query()
            ->orderBy('language')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
