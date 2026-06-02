<?php

namespace App\Http\Controllers;

use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        $progressSlideImages = collect();
        $progressSlideDirectory = public_path('images/game-img');

        if (File::isDirectory($progressSlideDirectory)) {
            $progressSlideImages = collect(File::files($progressSlideDirectory))
                ->filter(function (\SplFileInfo $file): bool {
                    return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif'], true);
                })
                ->shuffle()
                ->values()
                ->map(function (\SplFileInfo $file): string {
                    return asset('images/game-img/' . rawurlencode($file->getFilename()));
                });
        }

        return view('game', [
            'activeNav' => 'game',
            'progressSlideImages' => $progressSlideImages->all(),
        ] + $headerNavigationService->forUser($request->user()));
    }
}
