<?php

namespace App\Services\DictionarySubscriptions;

use App\Models\DictionaryShareInvitation;
use App\Models\DictionarySubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptDictionarySubscriptionService
{
    public const RESULT_ACCEPTED = 'accepted';
    public const RESULT_ALREADY_SUBSCRIBED = 'already_subscribed';
    public const RESULT_INVALID = 'invalid';
    public const RESULT_EXPIRED = 'expired';
    public const RESULT_EMAIL_MISMATCH = 'email_mismatch';

    /**
     * @return array{status:string, invitation:?DictionaryShareInvitation}
     */
    public function accept(User $user, string $rawToken): array
    {
        $invitation = $this->findByToken($rawToken);

        if (! $invitation instanceof DictionaryShareInvitation) {
            return ['status' => self::RESULT_INVALID, 'invitation' => null];
        }

        if ($this->hasExpired($invitation)) {
            if ($invitation->status === DictionaryShareInvitation::STATUS_PENDING) {
                $invitation->forceFill([
                    'status' => DictionaryShareInvitation::STATUS_EXPIRED,
                ])->save();
            }

            return ['status' => self::RESULT_EXPIRED, 'invitation' => $invitation];
        }

        if ($this->normalizeEmail($user->email) !== $this->normalizeEmail($invitation->target_email)) {
            return ['status' => self::RESULT_EMAIL_MISMATCH, 'invitation' => $invitation];
        }

        if ($invitation->status === DictionaryShareInvitation::STATUS_ACCEPTED) {
            return ['status' => self::RESULT_ALREADY_SUBSCRIBED, 'invitation' => $invitation];
        }

        $alreadySubscribed = DictionarySubscription::query()
            ->where('user_dictionary_id', $invitation->user_dictionary_id)
            ->where('subscriber_user_id', $user->id)
            ->exists();

        if ($alreadySubscribed) {
            $this->markAccepted($invitation);

            return ['status' => self::RESULT_ALREADY_SUBSCRIBED, 'invitation' => $invitation->fresh(['dictionary', 'owner'])];
        }

        DB::transaction(function () use ($invitation, $user): void {
            DictionarySubscription::query()->create([
                'user_dictionary_id' => $invitation->user_dictionary_id,
                'subscriber_user_id' => $user->id,
            ]);

            $this->markAccepted($invitation);
        });

        return ['status' => self::RESULT_ACCEPTED, 'invitation' => $invitation->fresh(['dictionary', 'owner'])];
    }

    public function findByToken(string $rawToken): ?DictionaryShareInvitation
    {
        $tokenHash = hash('sha256', $rawToken);

        return DictionaryShareInvitation::query()
            ->with(['dictionary', 'owner'])
            ->where('token_hash', $tokenHash)
            ->first();
    }

    private function hasExpired(DictionaryShareInvitation $invitation): bool
    {
        return $invitation->expires_at !== null && $invitation->expires_at->isPast();
    }

    private function markAccepted(DictionaryShareInvitation $invitation): void
    {
        $invitation->forceFill([
            'status' => DictionaryShareInvitation::STATUS_ACCEPTED,
            'accepted_at' => $invitation->accepted_at ?? now(),
        ])->save();
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
