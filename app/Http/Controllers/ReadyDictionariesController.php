<?php

namespace App\Http\Controllers;

use App\Models\UserDictionary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadyDictionariesController extends Controller
{
    public function index(Request $request): View
    {
        return view('ready-dictionaries', [
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
