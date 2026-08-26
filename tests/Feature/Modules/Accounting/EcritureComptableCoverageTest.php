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

test('EcritureComptable createEntry via service', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    $ecriture = $this->service->createEntry([
        'date_ecriture' => '2025-03-10',
        'date_piece' => '2025-03-10',
        'journal_type' => JournalType::ACHATS,
        'numero_piece' => 'ACH-COV-0001',
        'compte_numero' => $compte->numero,
        'libelle' => 'Test entry',
        'debit' => 42.50,
        'credit' => 0,
    ]);

    expect($ecriture)->toBeInstanceOf(EcritureComptable::class);
    expect((float) $ecriture->debit)->toEqualWithDelta(42.50, 0.01);
});

test('EcritureComptableService createBalancedPair throws on zero amount', function () {
    $this->expectException(InvalidArgumentException::class);

    $compte = CompteComptable::where('classe', 6)->first();
    $compte2 = CompteComptable::where('classe', 5)->first();

    $this->service->createBalancedPair(
        ['compte_numero' => $compte->numero, 'debit' => 0],
        ['compte_numero' => $compte2->numero, 'credit' => 0]
    );
});

test('EcritureComptableService lettrer throws on empty collection', function () {
    $this->expectException(InvalidArgumentException::class);

    $this->service->lettrer(collect(), 'LT-EMPTY');
});

test('EcritureComptableService getSoldeCompte with date filters', function () {
    $compte = CompteComptable::where('classe', 5)->first();

    EcritureComptable::factory()->debit(100)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-01-15',
    ]);
    EcritureComptable::factory()->credit(50)->create([
        'compte_numero' => $compte->numero,
        'date_ecriture' => '2025-06-15',
    ]);

    $soldeQ1 = $this->service->getSoldeCompte($compte->numero, '2025-01-01', '2025-03-31');
    $soldeQ2 = $this->service->getSoldeCompte($compte->numero, '2025-04-01', '2025-12-31');

    expect($soldeQ1)->toEqualWithDelta(100, 0.01);
    expect($soldeQ2)->toEqualWithDelta(-50, 0.01);
});

test('EcritureComptableService getSoldeJournal calculates correctly', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(200)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-03-01']);
    EcritureComptable::factory()->ofJournal(JournalType::BANQUE)
        ->credit(100)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-03-05']);

    $solde = $this->service->getSoldeJournal(JournalType::ACHATS);

    expect($solde)->toEqualWithDelta(200, 0.01);
});

test('EcritureComptableService getSoldeJournal with date filters', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(100)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-01-20']);
    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(200)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-07-20']);

    $soldeH1 = $this->service->getSoldeJournal(JournalType::ACHATS, '2025-01-01', '2025-06-30');
    $soldeH2 = $this->service->getSoldeJournal(JournalType::ACHATS, '2025-07-01', '2025-12-31');

    expect($soldeH1)->toEqualWithDelta(100, 0.01);
    expect($soldeH2)->toEqualWithDelta(200, 0.01);
});

test('EcritureComptableService generateNumeroPiece first of day', function () {
    $num = $this->service->generateNumeroPiece(JournalType::VENTES);

    expect($num)->toStartWith('VEN-');
    expect($num)->toContain(date('Ymd'));
    expect($num)->toEndWith('-0001');
});

test('EcritureComptable scope lettrées filters correctly', function () {
    EcritureComptable::factory()->lettrée('LT-A')->count(2)->create();
    EcritureComptable::factory()->count(3)->create();

    expect(EcritureComptable::lettrées()->count())->toBe(2);
});

test('EcritureComptable scope non lettrées filters correctly', function () {
    EcritureComptable::factory()->lettrée('LT-B')->count(1)->create();
    EcritureComptable::factory()->count(4)->create();

    expect(EcritureComptable::nonLettrées()->count())->toBe(4);
});

test('EcritureComptable scope duJournal filters correctly', function () {
    $compte = CompteComptable::where('classe', 6)->first();
    EcritureComptable::factory()->ofJournal(JournalType::ACHATS)
        ->debit(100)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-03-01']);
    EcritureComptable::factory()->ofJournal(JournalType::VENTES)
        ->credit(50)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-03-01']);

    expect(EcritureComptable::duJournal(JournalType::ACHATS)->count())->toBe(1);
});

test('EcritureComptable scope deDateRange filters correctly', function () {
    $compte = CompteComptable::where('classe', 6)->first();
    EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-01-15']);
    EcritureComptable::factory()->debit(200)->create(['compte_numero' => $compte->numero, 'date_ecriture' => '2025-06-15']);

    expect(EcritureComptable::deDateRange('2025-01-01', '2025-03-31')->count())->toBe(1);
    expect(EcritureComptable::deDateRange('2025-01-01', '2025-12-31')->count())->toBe(2);
});

test('EcritureComptable isDebit and isCredit and montant accessors', function () {
    $compte = CompteComptable::where('classe', 6)->first();

    $debit = EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero]);
    expect($debit->is_debit)->toBeTrue();
    expect($debit->is_credit)->toBeFalse();
    expect($debit->montant)->toEqualWithDelta(100, 0.01);

    $credit = EcritureComptable::factory()->credit(50)->create(['compte_numero' => $compte->numero]);
    expect($credit->is_debit)->toBeFalse();
    expect($credit->is_credit)->toBeTrue();
    expect($credit->montant)->toEqualWithDelta(-50, 0.01);
});

test('EcritureComptable compte relationship works', function () {
    $compte = CompteComptable::where('classe', 6)->first();
    $ecriture = EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero]);

    expect($ecriture->compte)->not->toBeNull();
    expect($ecriture->compte->numero)->toBe($compte->numero);
});

test('EcritureComptable isBalanced returns false for single entry', function () {
    $compte = CompteComptable::where('classe', 6)->first();
    $ecriture = EcritureComptable::factory()->debit(500)->create(['compte_numero' => $compte->numero]);

    expect($ecriture->isBalanced())->toBeFalse();
});

test('JournalType getCode and getLabel and getColor for all cases', function () {
    expect(JournalType::ACHATS->getCode())->toBe('ACH');
    expect(JournalType::ACHATS->getLabel())->toBe('Achats');
    expect(JournalType::ACHATS->getColor())->toBe('warning');

    expect(JournalType::VENTES->getCode())->toBe('VEN');
    expect(JournalType::VENTES->getLabel())->toBe('Ventes');
    expect(JournalType::VENTES->getColor())->toBe('success');

    expect(JournalType::BANQUE->getCode())->toBe('BQ');
    expect(JournalType::BANQUE->getLabel())->toBe('Banque');
    expect(JournalType::BANQUE->getColor())->toBe('info');

    expect(JournalType::CAISSE->getCode())->toBe('CAI');
    expect(JournalType::CAISSE->getLabel())->toBe('Caisse');
    expect(JournalType::CAISSE->getColor())->toBe('gray');

    expect(JournalType::OD->getCode())->toBe('OD');
    expect(JournalType::OD->getLabel())->toBe('OD (Operations Diverses)');
    expect(JournalType::OD->getColor())->toBe('primary');

    expect(JournalType::ANO->getCode())->toBe('ANO');
    expect(JournalType::ANO->getLabel())->toBe('A-nouveaux');
    expect(JournalType::ANO->getColor())->toBe('danger');
});

test('LettrageStatus getLabel and getColor for all cases', function () {
    expect(LettrageStatus::NON_LETTRÉE->getLabel())->toBe('Non lettrée');
    expect(LettrageStatus::NON_LETTRÉE->getColor())->toBe('danger');

    expect(LettrageStatus::PARTIELLEMENT_LETTRÉE->getLabel())->toBe('Partiellement lettrée');
    expect(LettrageStatus::PARTIELLEMENT_LETTRÉE->getColor())->toBe('warning');

    expect(LettrageStatus::LETTRÉE->getLabel())->toBe('Lettrée');
    expect(LettrageStatus::LETTRÉE->getColor())->toBe('success');
});

test('CompteComptable ecritures relationship', function () {
    $compte = CompteComptable::where('classe', 6)->first();
    EcritureComptable::factory()->debit(100)->create(['compte_numero' => $compte->numero]);
    EcritureComptable::factory()->debit(200)->create(['compte_numero' => $compte->numero]);

    expect($compte->ecritures)->toHaveCount(2);
});

test('CompteComptable full_label and classe_label attributes', function () {
    $compte = CompteComptable::firstOrCreate(
        ['numero' => '999100'],
        ['libelle' => 'Test Compte', 'classe' => 9, 'is_balance' => false]
    );

    expect($compte->full_label)->toBe('999100 - Test Compte');

    $c5 = CompteComptable::where('classe', 5)->first();
    expect($c5->classe_label)->toBe('Financier');

    $c6 = CompteComptable::where('classe', 6)->first();
    expect($c6->classe_label)->toBe('Charges');

    $c7 = CompteComptable::where('classe', 7)->first();
    expect($c7->classe_label)->toBe('Produits');

    $c8 = CompteComptable::where('classe', 8)->first();
    expect($c8->classe_label)->toBe('Résultat');
});

test('CompteComptable scope balances filters correctly', function () {
    $before = CompteComptable::balances()->count();

    CompteComptable::factory()->balance()->count(3)->create();

    expect(CompteComptable::balances()->count())->toBe($before + 3);
});

test('CompteComptable parent and children relationships', function () {
    $parent = CompteComptable::where('parent_id', null)->where('classe', 6)->first();

    $child = CompteComptable::create([
        'numero' => '629999',
        'libelle' => 'Enfant de test',
        'classe' => 6,
        'is_balance' => false,
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->not->toHaveCount(0);
    expect($child->parent)->not->toBeNull();
    expect($child->parent->id)->toBe($parent->id);
});
