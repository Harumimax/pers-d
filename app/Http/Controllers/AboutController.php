<?php

namespace App\Http\Controllers;

use App\Services\About\GlobalStatisticsService;
use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(
        Request $request,
        GlobalStatisticsService $globalStatisticsService,
        HeaderNavigationService $headerNavigationService,
    ): View
    {
        return view('about', [
            'globalStatistics' => $globalStatisticsService->summary(),
        ] + $headerNavigationService->forUser($request->user()));
    }
}
