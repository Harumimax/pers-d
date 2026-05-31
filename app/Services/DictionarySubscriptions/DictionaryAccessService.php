<?php

namespace App\Services\DictionarySubscriptions;

use App\Models\User;
use App\Models\UserDictionary;

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
}
