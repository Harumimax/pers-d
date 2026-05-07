<?php

namespace App\Http\Controllers;

use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        return view('game', [
            'activeNav' => 'game',
            'progressSlides' => range(1, 10),
        ] + $headerNavigationService->forUser($request->user()));
    }
}
