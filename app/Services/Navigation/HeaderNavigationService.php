<?php

namespace App\Services\Navigation;

use App\Models\ReadyDictionary;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Database\Eloquent\Collection;

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
        return $user?->dictionaries()
            ->orderByDesc('created_at')
            ->get(['id', 'name']) ?? new Collection();
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
