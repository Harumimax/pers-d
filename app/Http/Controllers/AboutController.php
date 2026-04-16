<?php

namespace App\Http\Controllers;

use App\Models\UserDictionary;
use App\Services\About\GlobalStatisticsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(Request $request, GlobalStatisticsService $globalStatisticsService): View
    {
        return view('about', [
            'headerDictionaries' => $this->headerDictionaries($request),
            'globalStatistics' => $globalStatisticsService->summary(),
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
