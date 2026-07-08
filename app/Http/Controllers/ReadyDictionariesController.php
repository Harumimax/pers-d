<?php

namespace App\Http\Controllers;

use App\Services\Navigation\HeaderNavigationService;
use App\Services\ReadyDictionaries\ReadyDictionaryCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ReadyDictionariesController extends Controller
{
    public function index(
        Request $request,
        ReadyDictionaryCatalogService $catalogService,
        HeaderNavigationService $headerNavigationService,
    ): View
    {
        return view('ready-dictionaries', $this->catalogViewData(
            $request,
            $catalogService,
            $headerNavigationService,
        ));
    }

    public function indexV2(
        Request $request,
        ReadyDictionaryCatalogService $catalogService,
        HeaderNavigationService $headerNavigationService,
    ): View
    {
        return view('ready-dictionaries-v2', $this->catalogViewData(
            $request,
            $catalogService,
            $headerNavigationService,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogViewData(
        Request $request,
        ReadyDictionaryCatalogService $catalogService,
        HeaderNavigationService $headerNavigationService,
    ): array
    {
        $catalog = $catalogService->catalog($request->only([
            'language',
            'level',
            'part_of_speech',
        ]));

        return [
            'readyDictionaries' => Arr::get($catalog, 'dictionaries'),
            'filterOptions' => Arr::get($catalog, 'filterOptions'),
            'selectedFilters' => Arr::get($catalog, 'selectedFilters'),
        ] + $headerNavigationService->forUser($request->user());
    }
}
