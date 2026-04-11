<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\GameSession;
use App\Models\UserDictionary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'headerDictionaries' => $this->headerDictionaries($request),
            'remainderStatistics' => $this->remainderStatistics($user?->id),
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

    /**
     * @return array<string, mixed>
     */
    private function remainderStatistics(?int $userId): array
    {
        if ($userId === null) {
            return $this->emptyRemainderStatistics();
        }

        $finishedSessionsQuery = GameSession::query()
            ->where('user_id', $userId)
            ->where('status', GameSession::STATUS_FINISHED);

        $summary = (clone $finishedSessionsQuery)
            ->selectRaw('COUNT(*) as sessions_count')
            ->selectRaw('MIN(finished_at) as first_finished_at')
            ->selectRaw('MAX(finished_at) as last_finished_at')
            ->selectRaw('COALESCE(SUM(total_words), 0) as total_words')
            ->selectRaw('COALESCE(SUM(correct_answers), 0) as correct_answers')
            ->first();

        $sessionsCount = (int) ($summary?->sessions_count ?? 0);
        $correctAnswers = (int) ($summary?->correct_answers ?? 0);
        $totalWords = (int) ($summary?->total_words ?? 0);
        $incorrectAnswers = max($totalWords - $correctAnswers, 0);
        $accuracyPercentage = $totalWords > 0
            ? round(($correctAnswers / $totalWords) * 100, 1)
            : null;

        return [
            'sessions_count' => $sessionsCount,
            'first_finished_at' => $summary?->first_finished_at,
            'last_finished_at' => $summary?->last_finished_at,
            'preferred_mode' => $this->preferredModeLabel($finishedSessionsQuery, $sessionsCount),
            'preferred_direction' => $this->preferredDirectionLabel($finishedSessionsQuery, $sessionsCount),
            'total_words' => $totalWords,
            'incorrect_answers' => $incorrectAnswers,
            'correct_answers' => $correctAnswers,
            'accuracy_percentage' => $accuracyPercentage,
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredModeLabel($finishedSessionsQuery, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $modeCounts = (clone $finishedSessionsQuery)
            ->select('mode', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mode')
            ->orderByDesc('aggregate')
            ->orderBy('mode')
            ->get();

        if ($modeCounts->count() > 1 && (int) $modeCounts[0]->aggregate === (int) $modeCounts[1]->aggregate) {
            return 'Both equally';
        }

        return match ($modeCounts->first()?->mode) {
            GameSession::MODE_CHOICE => 'Multiple choice',
            GameSession::MODE_MANUAL => 'Manual translation input',
            default => null,
        };
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredDirectionLabel($finishedSessionsQuery, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $directionCounts = (clone $finishedSessionsQuery)
            ->select('direction', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('direction')
            ->orderByDesc('aggregate')
            ->orderBy('direction')
            ->get();

        if ($directionCounts->count() > 1 && (int) $directionCounts[0]->aggregate === (int) $directionCounts[1]->aggregate) {
            return 'Both equally';
        }

        return match ($directionCounts->first()?->direction) {
            GameSession::DIRECTION_FOREIGN_TO_RU => 'Foreign language to Russian',
            GameSession::DIRECTION_RU_TO_FOREIGN => 'Russian to foreign language',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRemainderStatistics(): array
    {
        return [
            'sessions_count' => 0,
            'first_finished_at' => null,
            'last_finished_at' => null,
            'preferred_mode' => null,
            'preferred_direction' => null,
            'total_words' => 0,
            'incorrect_answers' => 0,
            'correct_answers' => 0,
            'accuracy_percentage' => null,
        ];
    }
}
