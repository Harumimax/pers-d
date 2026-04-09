<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Dictionaries\Index;
use App\Livewire\Dictionaries\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('dictionaries.index'))
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/about', function (Request $request) {
        return view('about', [
            'headerDictionaries' => $request->user()?->dictionaries()
                ->orderByDesc('created_at')
                ->get(['id', 'name']) ?? collect(),
        ]);
    })->name('about');
    Route::get('/remainder', function (Request $request) {
        return view('remainder', [
            'headerDictionaries' => $request->user()?->dictionaries()
                ->orderByDesc('created_at')
                ->get(['id', 'name']) ?? collect(),
        ]);
    })->name('remainder');

    Route::get('/dictionaries', Index::class)->name('dictionaries.index');
    Route::get('/dictionaries/{dictionary}', Show::class)->name('dictionaries.show');
});

require __DIR__.'/auth.php';
