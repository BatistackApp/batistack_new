<?php

use App\Http\Controllers\Public\PublicSafetyPassportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/public/rh/check-safety/{uuid}', PublicSafetyPassportController::class)->name('public.safety-check');

require __DIR__.'/settings.php';
// require __DIR__.'/test.php';
