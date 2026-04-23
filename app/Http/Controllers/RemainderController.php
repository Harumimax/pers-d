<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartGameRequest;
use App\Models\GameSession;
use App\Models\ReadyDictionary;
use App\Services\Navigation\HeaderNavigationService;
use App\Services\Remainder\PrepareGameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class RemainderController extends Controller
{
    private const DEMO_SESSION_IDS_KEY = 'remainder_demo_session_ids';
    private const DEMO_START_WINDOW_KEY = 'remainder_demo_start_window';
    private const DEMO_START_LIMIT_ATTEMPTS = 5;
    private const DEMO_START_LIMIT_DECAY_SECONDS = 60;

    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        $user = $request->user();

        return view('remainder', [
            'remainderDictionaries' => $user?->dictionaries()
                ->withCount('words')
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'language']) ?? collect(),
            'remainderReadyDictionaries' => ReadyDictionary::query()
                ->withCount('words')
                ->orderBy('language')
                ->orderBy('name')
                ->get(['id', 'name', 'language']),
        ] + $headerNavigationService->forUser($user));
    }

    public function store(StartGameRequest $request, PrepareGameService $prepareGameService): RedirectResponse
    {
        $this->ensureGuestDemoStartRateLimit($request);

        $result = $prepareGameService->prepare($request->user(), $request->validated());
        $gameSession = $result['gameSession'];
        $redirect = $gameSession->isDemo()
            ? redirect()->to($this->signedDemoSessionUrl($request, $gameSession))
            : redirect()->route('remainder.sessions.show', $gameSession);

        if ($result['notice'] !== null) {
            $redirect->with('gameNotice', $result['notice']);
        }

        return $redirect;
    }

    public function showSession(
        Request $request,
        GameSession $gameSession,
        HeaderNavigationService $headerNavigationService,
    ): View
    {
        if (! $gameSession->isDemo()) {
            abort_unless($request->user() !== null, 401);
            abort_if($gameSession->user_id !== $request->user()->id, 403);
        } else {
            abort_unless($request->hasValidSignature(), 403);
            abort_unless($this->sessionCanAccessDemoSession($request, $gameSession), 403);
        }

        return view('remainder-show', [
            'gameSession' => $gameSession,
        ] + $headerNavigationService->forUser($request->user()));
    }

    private function signedDemoSessionUrl(Request $request, GameSession $gameSession): string
    {
        $sessionIds = collect($request->session()->get(self::DEMO_SESSION_IDS_KEY, []))
            ->map(static fn ($id): int => (int) $id)
            ->push($gameSession->id)
            ->unique()
            ->take(-20)
            ->values()
            ->all();

        $request->session()->put(self::DEMO_SESSION_IDS_KEY, $sessionIds);

        return URL::temporarySignedRoute(
            'remainder.sessions.show',
            now()->addHours(4),
            ['gameSession' => $gameSession],
        );
    }

    private function sessionCanAccessDemoSession(Request $request, GameSession $gameSession): bool
    {
        return collect($request->session()->get(self::DEMO_SESSION_IDS_KEY, []))
            ->contains(static fn ($id): bool => (int) $id === $gameSession->id);
    }

    private function ensureGuestDemoStartRateLimit(Request $request): void
    {
        if ($request->user() !== null) {
            return;
        }

        $window = $request->session()->get(self::DEMO_START_WINDOW_KEY, []);
        $startedAt = (int) ($window['started_at'] ?? 0);
        $count = (int) ($window['count'] ?? 0);
        $now = now()->timestamp;

        if ($startedAt === 0 || ($now - $startedAt) >= self::DEMO_START_LIMIT_DECAY_SECONDS) {
            $startedAt = $now;
            $count = 0;
        }

        abort_if($count >= self::DEMO_START_LIMIT_ATTEMPTS, 429);

        $request->session()->put(self::DEMO_START_WINDOW_KEY, [
            'started_at' => $startedAt,
            'count' => $count + 1,
        ]);
    }
}
