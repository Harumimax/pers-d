<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserDictionary;
use App\Services\Profile\RemainderStatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, RemainderStatisticsService $remainderStatisticsService): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'headerDictionaries' => $this->headerDictionaries($request),
            'remainderStatistics' => $remainderStatisticsService->forUser($user?->id),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();
        $request->session()->put('ui_locale', $request->user()->preferred_locale);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return Collection<int, UserDictionary>
     */
    private function headerDictionaries(Request $request): Collection
    {
        return $request->user()?->dictionaries()
            ->orderByDesc('created_at')
            ->get(['id', 'name']) ?? collect();
    }
}
