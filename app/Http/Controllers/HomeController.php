<?php

namespace App\Http\Controllers;

use App\Models\ReadyDictionary;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'featuredReadyDictionaries' => ReadyDictionary::query()
                ->withCount('words')
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}
