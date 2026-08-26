<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Mail\Commerce\InvoiceDunningMail;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\DuePaymentService;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Mail::fake();

    // Configurer une entreprise et un client
    Company::factory()->create();
    $this->client = ThirdParty::factory()->create(['email' => 'client@test.com']);

    // Assurer que le taux TVA 0% existe (seeder-like)
    VatRate::firstOrCreate(['rate' => 0], ['name' => 'TVA 0% (Franchise / Indemnité)']);
});

it('sends friendly reminder at J+3 and updates dunning level', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::VALIDATED,
        'due_date' => now()->subDays(3)->startOfDay(),
        'dunning_level' => 0,
        'total_ttc' => 1000,
        'total_ht' => 1000,
        'total_tva' => 0,
    ]);

    // Ligne existante
    CustomerInvoiceItem::factory()->create([
        'customer_invoice_id' => $invoice->id,
        'total_ht' => 1000,
    ]);

    $service = new DuePaymentService;
    $service->processOverdueInvoices();

    Mail::assertQueued(InvoiceDunningMail::class, function ($mail) use ($invoice) {
        return $mail->invoice->id === $invoice->id && $mail->level === 1;
    });

    $invoice->refresh();
    expect($invoice->dunning_level)->toBe(1)
        ->and($invoice->last_dunning_at)->not->toBeNull();
});

it('sends formal notice at J+30 and adds fees and penalties', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::VALIDATED,
        'due_date' => now()->subDays(30)->startOfDay(),
        'dunning_level' => 2, // Prêt pour le niveau 3
        'total_ttc' => 1000,
        'total_ht' => 1000,
        'total_tva' => 0,
    ]);

    CustomerInvoiceItem::factory()->create([
        'customer_invoice_id' => $invoice->id,
        'total_ht' => 1000,
    ]);

    $service = new DuePaymentService;
    $service->processOverdueInvoices();

    Mail::assertQueued(InvoiceDunningMail::class, function ($mail) use ($invoice) {
        return $mail->invoice->id === $invoice->id && $mail->level === 3;
    });

    $invoice->refresh();
    expect($invoice->dunning_level)->toBe(3);

    // Vérifier l'ajout des frais (40€ + pénalités 10% sur 30 jours pour 1000€ = 8.22€)
    $items = $invoice->items;

    // Il devrait y avoir 3 lignes (originale + frais 40€ + pénalités)
    expect($items->count())->toBe(3);

    $feeItem = $items->firstWhere('name', 'Indemnité forfaitaire de recouvrement (loi LME)');
    expect($feeItem)->not->toBeNull()
        ->and($feeItem->total_ht)->toEqual(40.0);

    $penaltyItem = $items->firstWhere('name', 'Pénalités de retard');
    expect($penaltyItem)->not->toBeNull()
        ->and($penaltyItem->total_ht)->toBeGreaterThan(8.0);

    // Vérifier le nouveau total de la facture
    expect($invoice->total_ttc)->toBeGreaterThan(1048.0);
});

it('runs process dunning console command', function () {
    artisan('commerce:process-dunning')->assertExitCode(0);
});
