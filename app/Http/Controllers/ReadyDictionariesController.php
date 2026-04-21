<?php

namespace App\Http\Controllers;

use App\Services\Navigation\HeaderNavigationService;
use App\Services\ReadyDictionaries\ReadyDictionaryCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadyDictionariesController extends Controller
{
    public function index(
        Request $request,
        ReadyDictionaryCatalogService $catalogService,
        HeaderNavigationService $headerNavigationService,
    ): View
    {
        $catalog = $catalogService->catalog($request->only([
            'language',
            'level',
            'part_of_speech',
        ]));

        return view('ready-dictionaries', [
            'readyDictionaries' => $catalog['dictionaries'],
            'filterOptions' => $catalog['filterOptions'],
            'selectedFilters' => $catalog['selectedFilters'],
        ] + $headerNavigationService->forUser($request->user()));
    }
}
