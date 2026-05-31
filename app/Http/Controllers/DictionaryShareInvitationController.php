<?php

namespace App\Http\Controllers;

use App\Http\Requests\DictionarySubscriptions\AcceptDictionaryShareInvitationRequest;
use App\Http\Requests\DictionarySubscriptions\StoreDictionaryShareInvitationRequest;
use App\Models\User;
use App\Models\UserDictionary;
use App\Services\DictionarySubscriptions\AcceptDictionarySubscriptionService;
use App\Services\DictionarySubscriptions\CreateDictionaryShareInvitationService;
use App\Services\DictionarySubscriptions\DictionaryAccessService;
use App\Services\DictionarySubscriptions\SendDictionaryShareInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DictionaryShareInvitationController extends Controller
{
    public function store(
        StoreDictionaryShareInvitationRequest $request,
        UserDictionary $dictionary,
        DictionaryAccessService $dictionaryAccessService,
        CreateDictionaryShareInvitationService $createDictionaryShareInvitationService,
        SendDictionaryShareInvitationService $sendDictionaryShareInvitationService,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($dictionaryAccessService->canManage($user, $dictionary), 403);

        $result = $createDictionaryShareInvitationService->create(
            $user,
            $dictionary,
            (string) $request->string('target_email'),
        );

        $sendDictionaryShareInvitationService->send($result['invitation'], $result['raw_token']);

        return back()->with('status', __('dictionary-subscriptions.messages.invitation_sent'));
    }

    public function show(
        Request $request,
        string $token,
        AcceptDictionarySubscriptionService $acceptDictionarySubscriptionService,
    ): View {
        $invitation = $acceptDictionarySubscriptionService->findByToken($token);
        $user = $request->user();

        if ($invitation !== null && $user === null) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        $state = $this->resolveViewState($user, $invitation);

        return view('dictionary-subscriptions.show', [
            'invitation' => $invitation,
            'token' => $token,
            'state' => $state,
        ]);
    }

    public function accept(
        AcceptDictionaryShareInvitationRequest $request,
        string $token,
        AcceptDictionarySubscriptionService $acceptDictionarySubscriptionService,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $result = $acceptDictionarySubscriptionService->accept($user, $token);
        $invitation = $result['invitation'];

        return match ($result['status']) {
            AcceptDictionarySubscriptionService::RESULT_ACCEPTED,
            AcceptDictionarySubscriptionService::RESULT_ALREADY_SUBSCRIBED => redirect()
                ->route('dictionaries.index')
                ->with('status', __('dictionary-subscriptions.messages.subscription_added', [
                    'dictionary' => (string) $invitation?->dictionary?->name,
                ])),
            AcceptDictionarySubscriptionService::RESULT_EMAIL_MISMATCH => redirect()
                ->route('dictionary-subscriptions.show', ['token' => $token])
                ->withErrors([
                    'invitation' => __('dictionary-subscriptions.errors.email_mismatch'),
                ]),
            AcceptDictionarySubscriptionService::RESULT_EXPIRED => redirect()
                ->route('dictionary-subscriptions.show', ['token' => $token])
                ->withErrors([
                    'invitation' => __('dictionary-subscriptions.errors.expired'),
                ]),
            default => redirect()
                ->route('dictionary-subscriptions.show', ['token' => $token])
                ->withErrors([
                    'invitation' => __('dictionary-subscriptions.errors.invalid'),
                ]),
        };
    }

    private function resolveViewState(?User $user, ?\App\Models\DictionaryShareInvitation $invitation): string
    {
        if ($invitation === null) {
            return 'invalid';
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return 'expired';
        }

        if ($invitation->status === \App\Models\DictionaryShareInvitation::STATUS_ACCEPTED) {
            return 'accepted';
        }

        if ($user === null) {
            return 'guest';
        }

        if (mb_strtolower(trim((string) $user->email)) !== mb_strtolower(trim((string) $invitation->target_email))) {
            return 'email_mismatch';
        }

        return 'ready_to_accept';
    }
}
