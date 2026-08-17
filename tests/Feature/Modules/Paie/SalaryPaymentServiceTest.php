<?php

use App\Enums\Paie\PayslipStatus;
use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Banque\BankAccount;
use App\Models\Paie\Payslip;
use App\Models\Paie\SalaryPaymentRun;
use App\Models\User;
use App\Services\Banque\BridgePaymentService;
use App\Services\Paie\SalaryPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function bridgeMock(): MockInterface
{
    $mock = Mockery::mock(BridgePaymentService::class)->makePartial();
    app()->instance(BridgePaymentService::class, $mock);

    return $mock;
}

function payablePayslip(array $overrides = []): Payslip
{
    $payslip = Payslip::factory()->create(array_merge([
        'status' => PayslipStatus::VALIDATED,
        'net_paid' => 1350.00,
        'period' => '2026-07',
    ], $overrides));

    $payslip->employee->update([
        'iban' => 'FR2310096000301695931368H67',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);

    return $payslip;
}

function bridgeAccount(): BankAccount
{
    return BankAccount::factory()->create([
        'bridge_account_id' => 'acc-1',
        'bridge_bank_id' => '6',
        'iban' => 'FR7610011000200000000000018',
    ]);
}

it('creates a payment run from validated payable payslips', function () {
    $account = bridgeAccount();
    $payslip = payablePayslip();
    $user = User::factory()->create();

    $run = app(SalaryPaymentService::class)->createRun(collect([$payslip]), $account, $user);

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe(SalaryPaymentStatus::PENDING)
        ->and($run->bank_account_id)->toBe($account->id)
        ->and($run->period)->toBe('2026-07')
        ->and($run->total_amount)->toBe('1350.00')
        ->and($run->count)->toBe(1)
        ->and($run->idempotency_key)->not->toBeNull()
        ->and($run->lines)->toHaveCount(1)
        ->and($run->lines->first()->amount)->toBe('1350.00')
        ->and($run->lines->first()->payslip_id)->toBe($payslip->id);
});

it('produces a deterministic idempotency key for the same batch', function () {
    $account = bridgeAccount();
    $payslip = payablePayslip();
    $user = User::factory()->create();
    $service = app(SalaryPaymentService::class);

    $a = $service->createRun(collect([$payslip]), $account, $user);
    $b = $service->createRun(collect([$payslip]), $account, $user);

    expect($a->idempotency_key)->toBe($b->idempotency_key)
        ->and(SalaryPaymentRun::count())->toBe(1)
        ->and($a->is($b))->toBeTrue();
});

it('throws when the source account is not linked to a Bridge bank', function () {
    $account = BankAccount::factory()->create(['bridge_account_id' => 'acc-1', 'bridge_bank_id' => null]);
    $payslip = payablePayslip();

    app(SalaryPaymentService::class)->createRun(collect([$payslip]), $account, User::factory()->create());
})->throws(RuntimeException::class, 'bridge_bank_id');

it('throws when an employee iban is missing', function () {
    $account = bridgeAccount();
    $payslip = payablePayslip();
    $payslip->employee->update(['iban' => null]);

    app(SalaryPaymentService::class)->createRun(collect([$payslip]), $account, User::factory()->create());
})->throws(RuntimeException::class, 'IBAN manquant');

it('throws when no payable payslip is selected', function () {
    $account = bridgeAccount();
    $payslip = payablePayslip(['net_paid' => 0]);

    app(SalaryPaymentService::class)->createRun(collect([$payslip]), $account, User::factory()->create());
})->throws(RuntimeException::class, 'Aucun bulletin payant');

it('initiates a run against Bridge and stores the consent url', function () {
    $bridge = bridgeMock();
    $bridge->shouldReceive('initiatePaymentRequest')->once()->andReturn(['id' => 'req-1', 'url' => 'https://consent/initiate']);

    $account = bridgeAccount();
    $payslip = payablePayslip();
    $service = app(SalaryPaymentService::class);
    $run = $service->createRun(collect([$payslip]), $account, User::factory()->create());

    $service->initiateRun($run->fresh());

    $run->refresh();
    expect($run->status)->toBe(SalaryPaymentStatus::AWAITING_VALIDATION)
        ->and($run->bridge_payment_request_id)->toBe('req-1')
        ->and($run->consent_url)->toBe('https://consent/initiate')
        ->and($run->lines->first()->status)->toBe(SalaryPaymentStatus::AWAITING_VALIDATION);
});

it('does not re-initiate an already initiated run', function () {
    $bridge = bridgeMock();
    $bridge->shouldReceive('initiatePaymentRequest')->once()->andReturn(['id' => 'req-1', 'url' => 'https://consent/initiate']);

    $account = bridgeAccount();
    $payslip = payablePayslip();
    $service = app(SalaryPaymentService::class);
    $run = $service->createRun(collect([$payslip]), $account, User::factory()->create());

    expect($service->initiateRun($run->fresh()))->toBeTrue();

    $run->refresh();
    expect($run->status)->toBe(SalaryPaymentStatus::AWAITING_VALIDATION);

    // Le run est sorti de PENDING : toute tentative ultérieure est ignorée
    // et n'appelle pas de nouveau Bridge (anti-réinitiation).
    expect($service->initiateRun($run->fresh()))->toBeFalse();
});

it('marks the run, lines and payslips paid on a successful payment', function () {
    $bridge = bridgeMock();
    $bridge->shouldReceive('getPaymentRequestStatus')->once()->andReturn('ACSC');

    $account = bridgeAccount();
    $payslip = payablePayslip();
    $service = app(SalaryPaymentService::class);
    $run = $service->createRun(collect([$payslip]), $account, User::factory()->create());
    $run->update(['bridge_payment_request_id' => 'req-1']);

    $service->pollRun($run->fresh());

    $run->refresh();
    expect($run->status)->toBe(SalaryPaymentStatus::SUCCEEDED)
        ->and($run->lines->first()->status)->toBe(SalaryPaymentStatus::SUCCEEDED);

    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::PAID)
        ->and($payslip->payment_date)->not->toBeNull();
});

it('marks the run failed and keeps payslips unpaid on rejection', function () {
    $bridge = bridgeMock();
    $bridge->shouldReceive('getPaymentRequestStatus')->once()->andReturn('RJCT');

    $account = bridgeAccount();
    $payslip = payablePayslip();
    $service = app(SalaryPaymentService::class);
    $run = $service->createRun(collect([$payslip]), $account, User::factory()->create());
    $run->update(['bridge_payment_request_id' => 'req-1']);

    $service->pollRun($run->fresh());

    $run->refresh();
    expect($run->status)->toBe(SalaryPaymentStatus::FAILED)
        ->and($run->lines->first()->status)->toBe(SalaryPaymentStatus::FAILED);

    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);
});
