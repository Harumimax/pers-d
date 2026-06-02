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
        $finishImageUrl = null;

        if (File::isDirectory($progressSlideDirectory)) {
            $imageFiles = collect(File::files($progressSlideDirectory))
                ->filter(function (\SplFileInfo $file): bool {
                    return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif'], true);
                });

            $progressSlideImages = $imageFiles
                ->reject(function (\SplFileInfo $file): bool {
                    return str_starts_with(strtolower($file->getBasename('.' . $file->getExtension())), 'finish');
                })
                ->shuffle()
                ->values()
                ->map(function (\SplFileInfo $file): string {
                    return asset('images/game-img/' . rawurlencode($file->getFilename()));
                });

            $finishImage = $imageFiles->first(function (\SplFileInfo $file): bool {
                return str_starts_with(strtolower($file->getBasename('.' . $file->getExtension())), 'finish');
            });

            if ($finishImage instanceof \SplFileInfo) {
                $finishImageUrl = asset('images/game-img/' . rawurlencode($finishImage->getFilename()));
            }
        }

        return view('game', [
            'activeNav' => 'game',
            'progressSlideImages' => $progressSlideImages->all(),
            'finishImageUrl' => $finishImageUrl,
        ] + $headerNavigationService->forUser($request->user()));
    }
}
