<?php

use App\Http\Controllers\Api\ChecklistSyncController;
use App\Http\Controllers\Api\EtatDesLieuxSyncController;
use App\Http\Controllers\Api\JournalSyncController;
use App\Http\Controllers\Api\TechnicienSyncController;
use App\Http\Controllers\Banque\BridgeCallbackController;
use App\Http\Controllers\Commerce\StripePaymentController;
use App\Http\Controllers\Commerce\StripeWebhookController;
use App\Http\Controllers\Core\SignatureController;
use App\Http\Controllers\Core\SignatureWebhookController;
use App\Http\Controllers\Public\PublicSafetyPassportController;
use App\Http\Controllers\WebPushController;
use App\Livewire\Kiosk\BiometricClock;
use App\Livewire\Kiosk\BiometricEnrollment;
use App\Livewire\Onboarding\CandidateForm;
use App\Mail\Articles\SupplierQuoteRequestMail;
use App\Models\Articles\Item;
use App\Models\Vision3D\BimModel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/webpush/subscribe', [WebPushController::class, 'store'])->name('webpush.subscribe');
});

Route::get('/signature/{token}', [SignatureController::class, 'show'])->name('signature.show');
Route::post('/signature/{token}', [SignatureController::class, 'sign'])->name('signature.sign');

Route::get('/pay/invoice/{invoice}', [StripePaymentController::class, 'checkout'])->name('pay.invoice')->middleware('signed');
Route::get('/payment/success', [StripePaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [StripePaymentController::class, 'cancel'])->name('payment.cancel');

Route::post('/webhooks/docuseal', [SignatureWebhookController::class, 'handleDocuseal'])->name('webhooks.docuseal');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleWebhook'])->name('webhooks.stripe');

Route::get('/bim-viewer-headless/{id}', function ($id) {
    $model = BimModel::findOrFail($id);

    return view('bim.headless', compact('model'));
})->name('bim-viewer.headless');

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

Route::get('/pass-securite/{uuid}', [PublicSafetyPassportController::class, 'show'])->name('public.safety-check');

Route::get('/kiosk', BiometricClock::class)->name('kiosk.clock');
Route::get('/kiosk/enroll', BiometricEnrollment::class)->name('kiosk.enroll');

Route::get('/onboarding/{uuid}', CandidateForm::class)->name('public.onboarding');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/articles/{item}/request-quote', function (Item $item) {
        if (! $item->supplier_id) {
            return back()->with('error', 'Aucun fournisseur associé à cet article.');
        }

        $supplier = $item->supplier;
        if (! $supplier->email) {
            return back()->with('error', 'Le fournisseur n\'a pas d\'adresse email.');
        }

        Mail::to($supplier->email)->send(new SupplierQuoteRequestMail($item));

        Notification::make()
            ->title('Demande envoyée')
            ->body("L'email de demande de prix a été envoyé à {$supplier->name}.")
            ->success()
            ->send();

        return back();
    })->name('articles.request-quote');
    Route::get('/bridge/callback', BridgeCallbackController::class)->name('bridge.callback');
    Route::get('/bridge/renew', [BridgeCallbackController::class, 'renew'])->name('bridge.renew');

    // API Offline Technicien
    Route::get('/api/technicien/interventions', [TechnicienSyncController::class, 'index'])->name('technicien.api.interventions');
    Route::post('/api/technicien/sync', [TechnicienSyncController::class, 'sync'])->name('technicien.api.sync');

    // API Offline État des Lieux (chef de chantier)
    Route::get('/api/etat-des-lieux/contracts', [EtatDesLieuxSyncController::class, 'index'])->name('etat-des-lieux.api.contracts');
    Route::post('/api/etat-des-lieux/sync', [EtatDesLieuxSyncController::class, 'sync'])->name('etat-des-lieux.api.sync');

    // API Offline Journal de Chantier (chef de chantier)
    Route::get('/api/journal/chantiers', [JournalSyncController::class, 'chantiers'])->name('journal.api.chantiers');
    Route::get('/api/journal/logs', [JournalSyncController::class, 'index'])->name('journal.api.logs');
    Route::post('/api/journal/sync', [JournalSyncController::class, 'sync'])->name('journal.api.sync');

    // API Offline Checklists (chef de chantier)
    Route::get('/api/checklist/chantiers', [ChecklistSyncController::class, 'chantiers'])->name('checklist.api.chantiers');
    Route::get('/api/checklist/templates', [ChecklistSyncController::class, 'templates'])->name('checklist.api.templates');
    Route::get('/api/checklist/submissions', [ChecklistSyncController::class, 'submissions'])->name('checklist.api.submissions');
    Route::post('/api/checklist/sync', [ChecklistSyncController::class, 'sync'])->name('checklist.api.sync');
});
require __DIR__.'/settings.php';
// require __DIR__.'/test.php';
