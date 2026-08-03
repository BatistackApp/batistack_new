<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Enums\Commerce\PaymentType;
use App\Events\Commerce\PaymentCancelledEvent;
use App\Events\Commerce\PaymentRecordedEvent;
use App\Models\Commerce\Payment;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\PaymentRecordingService;
use App\Services\Commerce\PaymentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

beforeEach(function () {
    // Setup: Créer une company (dépendance globale)
    Company::factory()->create();

    // Inject le service
    $this->service = app(PaymentRecordingService::class);

    // Setup test data
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();

    // Fake events (Event::fake() existe toujours)
    Event::fake();
});

describe('PaymentRecordingService - recordPayment', function () {

    test('enregistre un paiement avec tous les paramètres', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 5000.00,
            payment_date: now(),
            reference: 'VIR-2026-00001',
            notes: 'Paiement client ABC'
        );

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->third_party_id)->toBe($this->customer->id)
            ->and($payment->type)->toBe(PaymentType::IN)
            ->and($payment->method)->toBe(PaymentMethod::BANK_TRANSFER)
            ->and($payment->amount)->toEqual(5000.00)
            ->and($payment->status)->toBe(PaymentStatus::COMPLETED)
            ->and($payment->reference)->toBe('VIR-2026-00001')
            ->and($payment->notes)->toBe('Paiement client ABC');
    });

    test('génère une référence unique si non fournie', function () {
        $payment1 = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        $payment2 = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 2000.00,
            payment_date: now()
        );

        // Les références doivent être différentes et au format IN-YYYY-NNNNN
        expect($payment1->reference)->toMatch('/^IN-\d{4}-\d{5}$/')
            ->and($payment2->reference)->toMatch('/^IN-\d{4}-\d{5}$/')
            ->and($payment1->reference)->not->toBe($payment2->reference);
    });

    test('dispache l\'événement PaymentRecordedEvent', function () {
        Event::fake([PaymentRecordedEvent::class]);
        $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 3000.00,
            payment_date: now()
        );

        Event::assertDispatched(PaymentRecordedEvent::class, function ($event) {
            return $event->payment->amount == 3000.00;
        });
    });

    test('valide que le montant est positif', function () {
        expect(function () {
            $this->service->recordPayment(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: -1000.00, // Montant négatif
                payment_date: now()
            );
        })->toThrow(\Exception::class, 'montant doit être > 0');
    });

    test('valide que le montant ne dépasse pas la limite', function () {
        expect(function () {
            $this->service->recordPayment(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 2_000_000.00, // > 1M
                payment_date: now()
            );
        })->toThrow(\Exception::class, 'limite');
    });

    test('valide que la date n\'est pas dans le futur', function () {
        expect(function () {
            $this->service->recordPayment(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 1000.00,
                payment_date: now()->addDays(10)
            );
        })->toThrow(\Exception::class, 'futur');
    });

    test('valide que la date n\'est pas trop ancienne', function () {
        expect(function () {
            $this->service->recordPayment(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 1000.00,
                payment_date: now()->subYears(11)
            );
        })->toThrow(\Exception::class, 'trop ancienne');
    });

    test('valide que le tiers existe', function () {
        $nonExistentCustomer = ThirdParty::make(['id' => 999]); // Non sauvegardé
        expect(function () use ($nonExistentCustomer) {
            $this->service->recordPayment(
                third_party: $nonExistentCustomer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 1000.00,
                payment_date: now()
            );
        })->toThrow(\Exception::class, 'tiers n\'existe pas');
    });

    test('enregistre un décaissement fournisseur', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->supplier,
            type: PaymentType::OUT,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 10000.00,
            payment_date: now()
        );

        expect($payment->type)->toBe(PaymentType::OUT)
            ->and($payment->reference)->toMatch('/^OUT-/');
    });

    // We can test default branch of match($type) by using reflection or passing a fake enum case if possible, but PHP 8.1 enums are strict. We will simulate it by mocking the enum, but wait, PaymentType only has IN and OUT. The `default` branch might be unreachable natively.

    test('rejette une référence dupliquée', function () {
        // Créer un premier paiement
        $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now(),
            reference: 'UNIQUE-REF-123'
        );

        // Essayer avec la même référence
        expect(function () {
            $this->service->recordPayment(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 2000.00,
                payment_date: now(),
                reference: 'UNIQUE-REF-123'
            );
        })->toThrow(\Exception::class, 'existe déjà');
    });
});

describe('PaymentRecordingService - recordPaymentWithAllocations', function () {
    test('enregistre un paiement et alloue sur les factures', function () {
        $invoice1 = CustomerInvoice::factory()->create(['client_id' => $this->customer->id, 'status' => \App\Enums\Commerce\InvoiceStatus::VALIDATED, 'total_ttc' => 5000.0]);
        $invoice2 = CustomerInvoice::factory()->create(['client_id' => $this->customer->id, 'status' => \App\Enums\Commerce\InvoiceStatus::VALIDATED, 'total_ttc' => 6000.0]);

        $payment = $this->service->recordPaymentWithAllocations(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 11000.00,
            payment_date: now(),
            allocations: [
                ['invoice' => $invoice1, 'amount' => 5000.0],
                ['invoice' => $invoice2, 'amount' => 6000.0],
            ]
        );

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->amount)->toEqual(11000.00)
            ->and($payment->allocations)->toHaveCount(2);
        
        expect($invoice1->fresh()->status)->toBe(\App\Enums\Commerce\InvoiceStatus::PAID)
            ->and($invoice2->fresh()->status)->toBe(\App\Enums\Commerce\InvoiceStatus::PAID);
    });

    test('échoue si le montant alloué ne correspond pas au montant total', function () {
        $invoice1 = CustomerInvoice::factory()->create(['client_id' => $this->customer->id, 'status' => \App\Enums\Commerce\InvoiceStatus::VALIDATED, 'total_ttc' => 5000.0]);

        expect(function () use ($invoice1) {
            $this->service->recordPaymentWithAllocations(
                third_party: $this->customer,
                type: PaymentType::IN,
                method: PaymentMethod::BANK_TRANSFER,
                amount: 6000.00, // != 5000
                payment_date: now(),
                allocations: [
                    ['invoice' => $invoice1, 'amount' => 5000.0],
                ]
            );
        })->toThrow(\Exception::class, 'Somme des allocations');
    });
});

describe('PaymentRecordingService - updatePayment', function () {

    test('met à jour le montant d\'un paiement', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        $updated = $this->service->updatePayment($payment, amount: 1500.00);

        expect($updated->amount)->toEqual(1500.00)
            ->and($updated->fresh()->amount)->toEqual(1500.00);
    });

    test('met à jour la date d\'un paiement', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()->subDays(5)
        );

        $newDate = now()->subDays(3);
        $updated = $this->service->updatePayment($payment, date: $newDate);

        expect($updated->payment_date->toDateString())->toBe($newDate->toDateString());
    });

    test('met à jour les notes d\'un paiement', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        $updated = $this->service->updatePayment($payment, notes: 'Nouvelles notes');

        expect($updated->notes)->toBe('Nouvelles notes');
    });

    test('enregistre un log pour la mise à jour', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        Log::shouldReceive('info')
            ->once()
            ->with('Payment updated', Mockery::on(function ($context) {
                return isset($context['updated']['amount']);
            }));

        $this->service->updatePayment($payment, amount: 2000.00);
    });

    test('rejette la mise à jour d\'un montant négatif', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        expect(function () use ($payment) {
            $this->service->updatePayment($payment, amount: -500.00);
        })->toThrow(\Exception::class, 'positif');
    });

    test('rejette la modification d\'un paiement annulé', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        // Annuler le paiement
        $this->service->cancelPayment($payment, 'Test');

        // Essayer de le modifier
        expect(function () use ($payment) {
            $this->service->updatePayment($payment, amount: 2000.00);
        })->toThrow(\Exception::class, 'Impossible');
    });
});

describe('PaymentRecordingService - cancelPayment', function () {

    test('annule un paiement et dé-lettre les factures', function () {
        $invoice = CustomerInvoice::factory()->create(['client_id' => $this->customer->id, 'status' => \App\Enums\Commerce\InvoiceStatus::VALIDATED, 'total_ttc' => 1000.0]);
        
        $payment = $this->service->recordPaymentWithAllocations(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now(),
            allocations: [['invoice' => $invoice, 'amount' => 1000.0]]
        );

        expect($invoice->fresh()->status)->toBe(\App\Enums\Commerce\InvoiceStatus::PAID);

        $cancelled = $this->service->cancelPayment($payment, 'Erreur de saisie');

        expect($cancelled->status)->toBe(PaymentStatus::FAILED)
            ->and($cancelled->notes)->toContain('Annulé')
            ->and($invoice->fresh()->status)->toBe(\App\Enums\Commerce\InvoiceStatus::VALIDATED)
            ->and($cancelled->allocations()->count())->toBe(0);
    });

    test('dispache l\'événement PaymentCancelledEvent', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        $this->service->cancelPayment($payment, 'Raison test');

        Event::assertDispatched(PaymentCancelledEvent::class, function ($event) use ($payment) {
            return $event->payment->id === $payment->id &&
                $event->reason === 'Raison test';
        });
    });

    test('enregistre un log d\'annulation', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        Log::shouldReceive('warning')
            ->once()
            ->with('Payment cancelled', Mockery::on(function ($context) {
                return $context['reason'] === 'Erreur';
            }));

        $this->service->cancelPayment($payment, 'Erreur');
    });

    test('rejette l\'annulation d\'un paiement déjà annulé', function () {
        $payment = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()
        );

        $this->service->cancelPayment($payment, 'Première annulation');

        expect(function () use ($payment) {
            $this->service->cancelPayment($payment, 'Deuxième annulation');
        })->toThrow(\Exception::class, 'déjà annulé');
    });
});

describe('PaymentRecordingService - duplicatePayment', function () {

    test('duplique un paiement existant', function () {
        $original = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()->subDays(5),
            notes: 'Paiement original'
        );

        $duplicate = $this->service->duplicatePayment($original);

        expect($duplicate->third_party_id)->toBe($original->third_party_id)
            ->and($duplicate->type)->toBe($original->type)
            ->and($duplicate->method)->toBe($original->method)
            ->and($duplicate->amount)->toEqual($original->amount)
            ->and($duplicate->reference)->not->toBe($original->reference)
            ->and($duplicate->notes)->toContain('Copie');
    });

    test('duplique avec une nouvelle date', function () {
        $original = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()->subDays(10)
        );

        $newDate = now();
        $duplicate = $this->service->duplicatePayment($original, $newDate);

        expect($duplicate->payment_date->toDateString())->toBe($newDate->toDateString())
            ->and($duplicate->amount)->toEqual($original->amount);
    });

    test('utilise la date actuelle si non spécifiée', function () {
        $original = $this->service->recordPayment(
            third_party: $this->customer,
            type: PaymentType::IN,
            method: PaymentMethod::BANK_TRANSFER,
            amount: 1000.00,
            payment_date: now()->subDays(30)
        );

        $duplicate = $this->service->duplicatePayment($original);

        expect($duplicate->payment_date->toDateString())->toBe(now()->toDateString());
    });
});
