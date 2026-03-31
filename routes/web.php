<?php

use App\Livewire\Dictionaries\Index;
use App\Livewire\Dictionaries\Show;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dictionaries', Index::class)->name('dictionaries.index');
    Route::get('/dictionaries/{dictionary}', Show::class)->name('dictionaries.show');
});
