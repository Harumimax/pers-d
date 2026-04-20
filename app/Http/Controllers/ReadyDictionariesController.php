<?php

namespace App\Http\Controllers;

use App\Models\UserDictionary;
use App\Services\ReadyDictionaries\ReadyDictionaryCatalogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadyDictionariesController extends Controller
{
    public function index(Request $request, ReadyDictionaryCatalogService $catalogService): View
    {
        $catalog = $catalogService->catalog($request->only([
            'language',
            'level',
            'part_of_speech',
        ]));

        return view('ready-dictionaries', [
            'headerDictionaries' => $this->headerDictionaries($request),
            'readyDictionaries' => $catalog['dictionaries'],
            'filterOptions' => $catalog['filterOptions'],
            'selectedFilters' => $catalog['selectedFilters'],
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
