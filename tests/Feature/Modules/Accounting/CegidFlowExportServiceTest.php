<?php

use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use App\Services\Accounting\CegidFlowExportService;
use Database\Seeders\Accounting\PcgSeeder;

beforeEach(function () {
    $this->seed(PcgSeeder::class);
    $this->service = new CegidFlowExportService();
});

test('CegidFlowExportService getData returns header and rows', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(300)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-05-10',
    ]);

    $data = $this->service->getData(2025);

    expect($data)->toHaveKeys(['header', 'rows']);
    expect($data['header'])->toHaveCount(11);
    expect($data['rows'])->toHaveCount(1);
    expect($data['rows'][0]['Debit'])->toBe(300.0);
});

test('CegidFlowExportService getData returns empty for year with no data', function () {
    $data = $this->service->getData(1999);

    expect($data['rows'])->toHaveCount(0);
});

test('CegidFlowExportService exportCsv creates file', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->credit(400)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-08-15',
    ]);

    $path = $this->service->exportCsv(2025);

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    $lines = explode("\r\n", $content);

    expect($lines)->toHaveCount(2);
    expect($lines[0])->toContain('JournalCode');
    expect($lines[0])->toContain('Devise');

    @unlink($path);
});

test('CegidFlowExportService getData includes Devise EUR', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-09-01',
    ]);

    $data = $this->service->getData(2025);

    expect($data['rows'][0]['Devise'])->toBe('EUR');
});

test('CegidFlowExportService getData includes lettrage in both fields', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->credit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-10-05',
        'lettrage' => 'LT-099',
    ]);

    $data = $this->service->getData(2025);

    expect($data['rows'][0]['EcritureLet'])->toBe('LT-099');
    expect($data['rows'][0]['Lettrage'])->toBe('LT-099');
});

test('CegidFlowExportService getData sorts by date', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-12-20',
    ]);
    EcritureComptable::factory()->debit(200)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-01-05',
    ]);

    $data = $this->service->getData(2025);

    expect($data['rows'][0]['EcritureDate'])->toBe('05/01/2025');
    expect($data['rows'][1]['EcritureDate'])->toBe('20/12/2025');
});
