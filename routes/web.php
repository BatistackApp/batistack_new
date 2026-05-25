<?php

use App\Http\Controllers\Public\PublicSafetyPassportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/health', function () {
    try {
        // Vérifier la connexion DB
        DB::connection()->getPdo();

        // Vérifier Redis
        Cache::driver('redis')->get('health-check');

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', 'unknown'),
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
})->name('health');

Route::get('/public/rh/check-safety/{uuid}', PublicSafetyPassportController::class)->name('public.safety-check');

require __DIR__.'/settings.php';
// require __DIR__.'/test.php';
