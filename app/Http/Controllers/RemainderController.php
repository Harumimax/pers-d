<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartGameRequest;
use App\Models\GameSession;
use App\Models\UserDictionary;
use App\Services\Remainder\PrepareGameService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RemainderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('remainder', [
            'headerDictionaries' => $this->headerDictionaries($request),
            'remainderDictionaries' => $user?->dictionaries()
                ->withCount('words')
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'language']) ?? collect(),
        ]);
    }

    public function store(StartGameRequest $request, PrepareGameService $prepareGameService): RedirectResponse
    {
        $result = $prepareGameService->prepare($request->user(), $request->validated());

        $redirect = redirect()->route('remainder.sessions.show', $result['gameSession']);

        if ($result['notice'] !== null) {
            $redirect->with('gameNotice', $result['notice']);
        }

        return $redirect;
    }

    public function showSession(Request $request, GameSession $gameSession): View
    {
        abort_unless($request->user() !== null, 401);
        abort_if($gameSession->user_id !== $request->user()->id, 403);

        return view('remainder-show', [
            'gameSession' => $gameSession,
            'headerDictionaries' => $this->headerDictionaries($request),
        ]);
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
