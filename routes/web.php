<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AboutContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadyDictionariesController;
use App\Http\Controllers\RemainderController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TgBotController;
use App\Livewire\Dictionaries\Index;
use App\Livewire\Dictionaries\Show;
use App\Livewire\ReadyDictionaries\Show as ReadyDictionaryShow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/interface-language', function (Request $request) {
    $language = (string) $request->input('language');
    $supportedLocales = config('app.supported_locales', [config('app.locale')]);

    if (in_array($language, $supportedLocales, true)) {
        $request->session()->put('ui_locale', $language);

        if ($request->user() !== null) {
            $request->user()->forceFill([
                'preferred_locale' => $language,
            ])->save();
        }
    }

    return redirect()->back();
})->name('interface-language.update');

Route::get('/ready-dictionaries', [ReadyDictionariesController::class, 'index'])->name('ready-dictionaries.index');
Route::get('/ready-dictionaries/{readyDictionary}', ReadyDictionaryShow::class)->name('ready-dictionaries.show');
Route::get('/remainder', [RemainderController::class, 'index'])->name('remainder');
Route::post('/remainder/sessions', [RemainderController::class, 'store'])->name('remainder.sessions.store');
Route::get('/remainder/sessions/{gameSession}', [RemainderController::class, 'showSession'])->name('remainder.sessions.show');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)->name('telegram.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('dictionaries.index'))
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/tg-bot', [TgBotController::class, 'index'])->name('tg-bot');
    Route::put('/tg-bot', [TgBotController::class, 'update'])->name('tg-bot.update');
    Route::patch('/tg-bot/random-words-status', [TgBotController::class, 'updateRandomWordsStatus'])
        ->name('tg-bot.random-words-status.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/about/contact', [AboutContactController::class, 'store'])
        ->middleware('throttle:about-contact')
        ->name('about.contact.store');
    Route::get('/dictionaries', Index::class)->name('dictionaries.index');
    Route::get('/dictionaries/{dictionary}', Show::class)->name('dictionaries.show');
});

require __DIR__.'/auth.php';

