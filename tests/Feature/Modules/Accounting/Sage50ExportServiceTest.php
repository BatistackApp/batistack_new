<?php

use App\Enums\Accounting\JournalType;
use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use App\Services\Accounting\Sage50ExportService;
use Database\Seeders\Accounting\PcgSeeder;

beforeEach(function () {
    $this->seed(PcgSeeder::class);
    $this->service = new Sage50ExportService;
});

test('Sage50ExportService getData returns header and rows', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(150)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-04-10',
    ]);

    $data = $this->service->getData(2025);

    expect($data)->toHaveKeys(['header', 'rows']);
    expect($data['header'])->toHaveCount(12);
    expect($data['rows'])->toHaveCount(1);
    expect($data['rows'][0]['Debit'])->toBe(150.0);
});

test('Sage50ExportService getData returns empty for year with no data', function () {
    $data = $this->service->getData(1999);

    expect($data['rows'])->toHaveCount(0);
});

test('Sage50ExportService exportCsv creates file', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->credit(200)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-07-01',
    ]);

    $path = $this->service->exportCsv(2025);

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    $lines = explode("\r\n", $content);

    expect($lines)->toHaveCount(2);
    expect($lines[0])->toContain('JournalCode');
    expect($lines[0])->toContain('CompteNum');
    expect($lines[1])->toContain($compte->numero);

    @unlink($path);
});

test('Sage50ExportService exportCsv uses semicolon separator by default', function () {
    $path = $this->service->exportCsv(2025);

    $content = file_get_contents($path);
    expect($content)->toContain(';');

    @unlink($path);
});

test('Sage50ExportService getData includes journal code and label', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(100)->create([
            'compte_numero' => $compte->numero,
            'date_ecriture' => '2025-02-15',
        ]);

    $data = $this->service->getData(2025);

    expect($data['rows'][0]['JournalCode'])->toBe('ACH');
    expect($data['rows'][0]['JournalLib'])->toBe('Achats');
});

test('Sage50ExportService getData handles lettrage', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-03-20',
        'lettrage' => 'LT-042',
    ]);

    $data = $this->service->getData(2025);

    expect($data['rows'][0]['EcritureLet'])->toBe('LT-042');
});
