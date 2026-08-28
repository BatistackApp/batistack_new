<?php

use App\Enums\Accounting\JournalType;
use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use App\Services\Accounting\FecExportService;
use Database\Seeders\Accounting\PcgSeeder;

beforeEach(function () {
    $this->seed(PcgSeeder::class);
    $this->service = new FecExportService;
});

test('FecExportService getFecData returns header and rows', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-03-15',
    ]);

    $data = $this->service->getFecData(2025);

    expect($data)->toHaveKeys(['header', 'rows']);
    expect($data['header'])->toHaveCount(18);
    expect($data['rows'])->toHaveCount(1);
    expect($data['rows'][0]['CompteNum'])->toBe($compte->numero);
    expect($data['rows'][0]['Debit'])->toBe(100.0);
});

test('FecExportService getFecData returns empty rows for year with no data', function () {
    $data = $this->service->getFecData(1999);

    expect($data['rows'])->toHaveCount(0);
});

test('FecExportService exportFec creates file', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->credit(250)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-06-20',
    ]);

    $path = $this->service->exportFec(2025, '999999999');

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    $lines = explode("\r\n", $content);

    // Header + 1 data line
    expect($lines)->toHaveCount(2);
    expect($lines[0])->toContain('JournalCode');
    expect($lines[0])->toContain('CompteNum');
    expect($lines[1])->toContain($compte->numero);

    // Clean up
    @unlink($path);
});

test('FecExportService exportFec uses correct filename format', function () {
    $path = $this->service->exportFec(2025, '123456789');

    $expectedFilename = '123456789FEC20251231.txt';
    expect(file_exists($path))->toBeTrue();
    expect(basename($path))->toBe($expectedFilename);

    @unlink($path);
});

test('FecExportService exportFec handles multiple journal types', function () {
    $compte6 = CompteComptable::where('classe', 6)->first();
    $compte5 = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(100)->create(['compte_numero' => $compte6->numero, 'date_ecriture' => '2025-01-10']);
    EcritureComptable::factory()->ofJournal(JournalType::BANQUE)
        ->credit(100)->create(['compte_numero' => $compte5->numero, 'date_ecriture' => '2025-01-12']);

    $data = $this->service->getFecData(2025);

    expect($data['rows'])->toHaveCount(2);
    expect($data['rows'][0]['JournalCode'])->toBe('ACH');
    expect($data['rows'][1]['JournalCode'])->toBe('BQ');
});

test('FecExportService getBalanceGenerale calculates correctly', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(200)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-04-01']);
    EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-05-01']);
    EcritureComptable::factory()->credit(50)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-06-01']);

    $balance = $this->service->getBalanceGenerale(2025);

    $found = collect($balance)->firstWhere('compte', $compte->numero);

    expect($found)->not->toBeNull();
    expect($found['total_debit'])->toEqualWithDelta(300, 0.01);
    expect($found['total_credit'])->toEqualWithDelta(50, 0.01);
    expect($found['solde_debit'])->toEqualWithDelta(250, 0.01);
    expect($found['solde_credit'])->toEqualWithDelta(0, 0.01);
});

test('FecExportService getBalanceGenerale returns sorted by account number', function () {
    $compte5 = CompteComptable::where('classe', 5)->first();
    $compte6 = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte6->numero, 'date_ecriture' => '2025-01-01']);
    EcritureComptable::factory()->credit(100)->create(['compte_numero' => $compte5->numero, 'date_ecriture' => '2025-01-02']);

    $balance = $this->service->getBalanceGenerale(2025);

    expect($balance[0]['compte'])->toBeLessThan($balance[1]['compte']);
});

test('FecExportService exportFec handles lettrage code', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-07-01',
        'lettrage' => 'LT-001',
    ]);

    $data = $this->service->getFecData(2025);

    expect($data['rows'][0]['EcritureLet'])->toBe('LT-001');
});
