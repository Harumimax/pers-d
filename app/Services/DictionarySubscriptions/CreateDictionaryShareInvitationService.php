<?php

namespace App\Services\DictionarySubscriptions;

use App\Models\DictionaryShareInvitation;
use App\Models\User;
use App\Models\UserDictionary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateDictionaryShareInvitationService
{
    /**
     * @return array{invitation: DictionaryShareInvitation, raw_token: string}
     */
    public function create(User $owner, UserDictionary $dictionary, string $targetEmail): array
    {
        $normalizedEmail = $this->normalizeEmail($targetEmail);

        if ($normalizedEmail === $this->normalizeEmail($owner->email)) {
            throw ValidationException::withMessages([
                'target_email' => __('dictionary-subscriptions.errors.cannot_invite_owner'),
            ]);
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($existingUser !== null
            && $dictionary->subscriptions()->where('subscriber_user_id', $existingUser->id)->exists()) {
            throw ValidationException::withMessages([
                'target_email' => __('dictionary-subscriptions.errors.target_already_subscribed'),
            ]);
        }

        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);

        /** @var DictionaryShareInvitation $invitation */
        $invitation = DB::transaction(function () use ($dictionary, $owner, $normalizedEmail, $tokenHash): DictionaryShareInvitation {
            DictionaryShareInvitation::query()
                ->where('user_dictionary_id', $dictionary->id)
                ->whereRaw('LOWER(target_email) = ?', [$normalizedEmail])
                ->where('status', DictionaryShareInvitation::STATUS_PENDING)
                ->update([
                    'status' => DictionaryShareInvitation::STATUS_CANCELLED,
                    'updated_at' => now(),
                ]);

            return DictionaryShareInvitation::query()->create([
                'user_dictionary_id' => $dictionary->id,
                'owner_user_id' => $owner->id,
                'target_email' => $normalizedEmail,
                'token_hash' => $tokenHash,
                'status' => DictionaryShareInvitation::STATUS_PENDING,
                'expires_at' => now()->addDays(7),
            ]);
        });

        return [
            'invitation' => $invitation,
            'raw_token' => $rawToken,
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
