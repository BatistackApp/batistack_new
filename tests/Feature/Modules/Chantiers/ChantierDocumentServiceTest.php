<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Commerce\CustomerOrder;
use App\Models\Tiers\ThirdParty;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\User;
use App\Services\Chantiers\ChantierDocumentService;
use App\Services\Chantiers\ChantierAnalyticService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

use App\Enums\Commerce\OrderStatus;

beforeEach(function () {
    $this->customer = ThirdParty::factory()->create(['name' => 'Test Customer', 'type' => ThirdPartyType::CLIENT]);
    $this->user = User::factory()->create();
    $this->order = CustomerOrder::create([
        'reference' => 'CO-CHANTIER',
        'client_id' => $this->customer->id,
        'responsable_id' => $this->user->id,
        'status' => OrderStatus::CONFIRMED
    ]);
    
    $this->chantier = Chantier::create([
        'reference' => 'CH-TEST',
        'name' => 'Chantier Test',
        'customer_order_id' => $this->order->id,
        'client_id' => $this->customer->id,
        'responsable_id' => $this->user->id,
        'status' => 'in_progress',
        'address' => '123 Test Street',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
    
    $this->company = \App\Models\Core\Company::create([
        'legal_name' => 'Test Company',
        'address' => '123 Test Street',
        'zip_code' => '75000',
        'city' => 'Paris',
        'phone' => '0102030405',
        'email' => 'test@test.com',
        'siret' => '12345678901234',
        'capital' => 10000,
        'vat_number' => 'FR123456789'
    ]);
    
    $this->service = app(ChantierDocumentService::class);
    
    // Config fake disk
    config(['filesystems.default' => 'local']);
    Storage::fake('local');
    
    // We can't easily test Spatie\Browsershot in CI without Node/Puppeteer, 
    // so we just mock the PDF generation methods to return a path, or 
    // test the view rendering instead. But ChantierDocumentService uses 
    // \App\Services\Core\DocumentService::generatePdf.
    // Instead of executing, let's mock the `generatePdf` method of DocumentService
    // Actually, ChantierDocumentService extends \App\Services\Core\DocumentService
    // So we can mock the generatePdf call on a partial mock.
});

it('generates start order PDF', function () {
    $path = $this->service->generateStartOrder($this->chantier);
    expect($path)->toContain('documents/chantiers/orders/os_CH-TEST.pdf');
});

it('generates handover protocol PDF', function () {
    $path = $this->service->generateHandoverProtocol($this->chantier);
    expect($path)->toContain('documents/chantiers/legal/pv_reception_CH-TEST.pdf');
});

it('generates rentability report PDF', function () {
    $path = $this->service->generateRentabilityReport($this->chantier);
    expect($path)->toContain('documents/chantiers/reports/bilan_CH-TEST.pdf');
});

it('generates weekly journal PDF', function () {
    // Simuler des logs
    ChantierLog::create([
        'chantier_id' => $this->chantier->id,
        'date' => Carbon::now()->startOfWeek(),
        'weather_morning' => 'sunny',
        'weather_afternoon' => 'sunny',
        'content' => 'Test log',
        'user_id' => App\Models\User::factory()->create()->id
    ]);

    $path = $this->service->generateWeeklyJournal($this->chantier, Carbon::now()->startOfWeek());
    expect($path)->toContain('documents/chantiers/journals/journal_CH-TEST');
});
