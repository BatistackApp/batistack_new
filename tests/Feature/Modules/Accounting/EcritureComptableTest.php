<?php

use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\LettrageStatus;
use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use App\Services\Accounting\EcritureComptableService;
use Database\Seeders\Accounting\PcgSeeder;

beforeEach(function () {
    $this->seed(PcgSeeder::class);
    $this->service = new EcritureComptableService;
});

test('EcritureComptable can be created via factory', function () {
    $ecriture = EcritureComptable::factory()->create();

    expect($ecriture)->toBeInstanceOf(EcritureComptable::class);
    expect($ecriture->date_ecriture)->not->toBeNull();
    expect($ecriture->numero_piece)->not->toBeEmpty();
});

test('EcritureComptable debit/credit validation prevents both non-zero', function () {
    $this->expectException(InvalidArgumentException::class);

    EcritureComptable::factory()->create([
        'debit' => 100,
        'credit' => 100,
    ]);
});

test('EcritureComptable debit/credit validation prevents both zero', function () {
    $this->expectException(InvalidArgumentException::class);

    EcritureComptable::factory()->create([
        'debit' => 0,
        'credit' => 0,
    ]);
});

test('EcritureComptable montant attribute calculates net amount', function () {
    $ecriture = EcritureComptable::factory()->debit(500)->create();

    expect((float) $ecriture->montant)->toEqualWithDelta(500, 0.01);
    expect($ecriture->is_debit)->toBeTrue();
    expect($ecriture->is_credit)->toBeFalse();

    $ecritureCredit = EcritureComptable::factory()->credit(300)->create();

    expect((float) $ecritureCredit->montant)->toEqualWithDelta(-300, 0.01);
    expect($ecritureCredit->is_credit)->toBeTrue();
});

test('EcritureComptable scope deDateRange filters correctly', function () {
    EcritureComptable::factory()->create(['date_ecriture' => '2025-01-15']);
    EcritureComptable::factory()->create(['date_ecriture' => '2025-06-15']);
    EcritureComptable::factory()->create(['date_ecriture' => '2025-12-15']);

    $q1 = EcritureComptable::deDateRange('2025-01-01', '2025-03-31')->count();
    $q2 = EcritureComptable::deDateRange('2025-04-01', '2025-09-30')->count();

    expect($q1)->toBe(1);
    expect($q2)->toBe(1);
});

test('EcritureComptable scope duJournal filters correctly', function () {
    EcritureComptable::factory()->ofJournal(JournalType::BANQUE)->count(3)->create();
    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)->count(2)->create();

    expect(EcritureComptable::duJournal(JournalType::BANQUE)->count())->toBe(3);
    expect(EcritureComptable::duJournal(JournalType::ACHATS)->count())->toBe(2);
});

test('EcritureComptable scope nonLettrées filters correctly', function () {
    EcritureComptable::factory()->count(3)->create(['lettrage_status' => LettrageStatus::NON_LETTRÉE]);
    EcritureComptable::factory()->lettrée()->count(2)->create();

    expect(EcritureComptable::nonLettrées()->count())->toBe(3);
});

test('EcritureComptable scope lettrées filters correctly', function () {
    EcritureComptable::factory()->count(2)->create(['lettrage_status' => LettrageStatus::LETTRÉE]);
    EcritureComptable::factory()->count(3)->create(['lettrage_status' => LettrageStatus::NON_LETTRÉE]);

    expect(EcritureComptable::lettrées()->count())->toBe(2);
});

test('EcritureComptableService createBalancedPair creates two entries', function () {
    $compteDebit = CompteComptable::where('classe', 6)->first();
    $compteCredit = CompteComptable::where('classe', 5)->first();

    [$ecriture1, $ecriture2] = $this->service->createBalancedPair(
        [
            'date_ecriture' => now()->toDateString(),
            'date_piece' => now()->toDateString(),
            'journal_type' => JournalType::ACHATS,
            'numero_piece' => 'ACH-20250101-0001',
            'compte_numero' => $compteDebit->numero,
            'libelle' => 'Achat fournitures',
            'debit' => 150,
        ],
        [
            'date_ecriture' => now()->toDateString(),
            'date_piece' => now()->toDateString(),
            'journal_type' => JournalType::ACHATS,
            'numero_piece' => 'ACH-20250101-0001',
            'compte_numero' => $compteCredit->numero,
            'libelle' => 'Achat fournitures',
            'credit' => 150,
        ]
    );

    expect((float) $ecriture1->debit)->toEqualWithDelta(150, 0.01);
    expect((float) $ecriture1->credit)->toEqualWithDelta(0, 0.01);
    expect((float) $ecriture2->debit)->toEqualWithDelta(0, 0.01);
    expect((float) $ecriture2->credit)->toEqualWithDelta(150, 0.01);
});

test('EcritureComptableService lettrer updates status', function () {
    $compte1 = CompteComptable::where('classe', 6)->first();
    $compte2 = CompteComptable::where('classe', 5)->first();

    $e1 = EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte1->numero, 'lettrage_status' => LettrageStatus::NON_LETTRÉE]);
    $e2 = EcritureComptable::factory()->credit(100)->create(['compte_numero' => $compte2->numero, 'lettrage_status' => LettrageStatus::NON_LETTRÉE]);
    $ecritures = collect([$e1, $e2]);

    $this->service->lettrer($ecritures, 'LT-001');

    expect($e1->fresh()->lettrage)->toBe('LT-001');
    expect($e1->fresh()->lettrage_status)->toBe(LettrageStatus::LETTRÉE);
    expect($e2->fresh()->lettrage)->toBe('LT-001');
    expect($e2->fresh()->lettrage_status)->toBe(LettrageStatus::LETTRÉE);
});

test('EcritureComptableService lettrer throws on unbalanced entries', function () {
    $this->expectException(InvalidArgumentException::class);

    $ecritures = collect([
        EcritureComptable::factory()->debit(100)->create(),
        EcritureComptable::factory()->debit(200)->create(),
    ]);

    $this->service->lettrer($ecritures, 'LT-002');
});

test('EcritureComptableService dellettrer clears lettrage', function () {
    $e1 = EcritureComptable::factory()->lettrée('LT-003')->create();
    $e2 = EcritureComptable::factory()->lettrée('LT-003')->create();
    $ecritures = collect([$e1, $e2]);

    $this->service->dellettrer($ecritures);

    expect($e1->fresh()->lettrage)->toBeNull();
    expect($e1->fresh()->lettrage_status)->toBe(LettrageStatus::NON_LETTRÉE);
    expect($e2->fresh()->lettrage)->toBeNull();
    expect($e2->fresh()->lettrage_status)->toBe(LettrageStatus::NON_LETTRÉE);
});

test('EcritureComptableService getSoldeCompte calculates correctly', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->debit(500)->create(['compte_numero' => $compte->numero]);
    EcritureComptable::factory()->credit(200)->create(['compte_numero' => $compte->numero]);

    $solde = $this->service->getSoldeCompte($compte->numero);

    expect($solde)->toEqualWithDelta(300, 0.01);
});

test('EcritureComptableService generateNumeroPiece creates sequential numbers', function () {
    $num1 = $this->service->generateNumeroPiece(JournalType::ACHATS);

    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)->create([
        'numero_piece' => $num1,
    ]);

    $num2 = $this->service->generateNumeroPiece(JournalType::ACHATS);

    expect($num1)->toStartWith('ACH-');
    expect($num2)->toStartWith('ACH-');
    expect($num1)->not->toBe($num2);
});

test('EcritureComptableService getBalanceCompte returns full breakdown', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero]);
    EcritureComptable::factory()->debit(50)->create(['compte_numero' => $compte->numero]);
    EcritureComptable::factory()->credit(30)->create(['compte_numero' => $compte->numero]);

    $balance = $this->service->getBalanceCompte($compte->numero);

    expect($balance['compte'])->toBe($compte->numero);
    expect($balance['total_debit'])->toEqualWithDelta(150, 0.01);
    expect($balance['total_credit'])->toEqualWithDelta(30, 0.01);
    expect($balance['solde'])->toEqualWithDelta(120, 0.01);
    expect($balance['nombre_ecritures'])->toBe(3);
});

test('EcritureComptable isBalanced returns true for zero net', function () {
    $ecriture = EcritureComptable::factory()->debit(500)->create();

    expect($ecriture->isBalanced())->toBeFalse();
});
