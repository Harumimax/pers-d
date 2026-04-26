<?php

namespace App\Http\Controllers;

use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TgBotController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        return view('tg-bot', [
            'activeNav' => 'tg-bot',
        ] + $headerNavigationService->forUser($request->user()));
    }
}
