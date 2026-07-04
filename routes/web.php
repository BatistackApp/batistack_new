<?php

use App\Http\Controllers\Public\PublicSafetyPassportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/signature/{token}', [\App\Http\Controllers\Core\SignatureController::class, 'show'])->name('signature.show');
Route::post('/signature/{token}', [\App\Http\Controllers\Core\SignatureController::class, 'sign'])->name('signature.sign');

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

Route::get('/pass-securite/{uuid}', [\App\Http\Controllers\RH\PublicSafetyCheckController::class, 'show'])->name('public.safety-check');

Route::get('/kiosk', \App\Livewire\Kiosk\BiometricClock::class)->name('kiosk.clock');
Route::get('/kiosk/enroll', \App\Livewire\Kiosk\BiometricEnrollment::class)->name('kiosk.enroll');

Route::get('/onboarding/{uuid}', \App\Livewire\Onboarding\CandidateForm::class)->name('public.onboarding');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/articles/{item}/request-quote', function (\App\Models\Articles\Item $item) {
        if (!$item->supplier_id) {
            return back()->with('error', 'Aucun fournisseur associé à cet article.');
        }

        $supplier = $item->supplier;
        if (!$supplier->email) {
            return back()->with('error', 'Le fournisseur n\'a pas d\'adresse email.');
        }

        \Illuminate\Support\Facades\Mail::to($supplier->email)->send(new \App\Mail\Articles\SupplierQuoteRequestMail($item));

        \Filament\Notifications\Notification::make()
            ->title('Demande envoyée')
            ->body("L'email de demande de prix a été envoyé à {$supplier->name}.")
            ->success()
            ->send();

        return back();
    })->name('articles.request-quote');
});
require __DIR__.'/settings.php';
// require __DIR__.'/test.php';
