<?php

use App\Enums\RH\ContractType;
use App\Enums\RH\TerminationType;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\RH\ContractTerminationService;
use App\Services\RH\RHDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $mock = Mockery::mock(RHDocumentService::class);
    $mock->shouldReceive('generateContract')->andReturn('dummy_path');
    $mock->shouldReceive('generateCdiTerminationLetter')->andReturn('dummy_path');
    $mock->shouldReceive('generateRuptureConventionnelle')->andReturn('dummy_path');
    $mock->shouldReceive('generateSoldeDeToutCompte')->andReturn('dummy_path');
    $this->app->instance(RHDocumentService::class, $mock);
});

it('terminates a CDI contract and sets termination fields', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'job_title' => 'Conducteur de travaux',
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $terminated = $service->terminate(
        contract: $contract,
        type: TerminationType::LICENCIEMENT,
        reason: 'Faute professionnelle',
    );

    expect($terminated->terminated_at)->not->toBeNull();
    expect($terminated->termination_type)->toBe(TerminationType::LICENCIEMENT);
    expect($terminated->termination_reason)->toBe('Faute professionnelle');
    expect($terminated->notice_end_date)->not->toBeNull();
    expect($terminated->end_date)->not->toBeNull();
    expect($terminated->isTerminated())->toBeTrue();
});

it('terminates a CDD contract without notice', function () {
    Notification::fake();
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $terminationDate = now()->addWeek()->startOfDay();

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'hourly_rate' => 20,
    ]);

    $terminated = app(ContractTerminationService::class)->terminateCdd(
        contract: $contract,
        terminationDate: $terminationDate,
        reason: 'Rupture négociée',
        amount: 500,
    );

    expect($terminated->termination_type)->toBe(TerminationType::RUPTURE_ANTICIPEE_CDD);
    expect($terminated->termination_reason)->toBe('Rupture négociée');
    expect($terminated->end_date->toDateString())->toBe($terminationDate->toDateString());
    expect($terminated->notice_end_date)->toBeNull();
    expect($terminated->termination_amount)->toBe('500.00');
    expect($terminated->isTerminated())->toBeTrue();
    Notification::assertSentTo($employee, \App\Notifications\RH\ContractTerminatedNotification::class);
});

it('rejects CDD termination for a CDI contract', function () {
    $contract = Contract::factory()->create([
        'type' => ContractType::CDI,
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
    ]);

    expect(fn () => app(ContractTerminationService::class)->terminateCdd($contract, now()))
        ->toThrow(LogicException::class);
});

it('rejects CDD termination outside the contract dates', function () {
    $contract = Contract::factory()->create([
        'type' => ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'hourly_rate' => 20,
    ]);
    $service = app(ContractTerminationService::class);

    expect(fn () => $service->terminateCdd($contract, $contract->start_date->copy()->subDay()))
        ->toThrow(LogicException::class);
    expect(fn () => $service->terminateCdd($contract, $contract->end_date->copy()))
        ->toThrow(LogicException::class);
});

it('rejects an already terminated or inactive CDD', function () {
    $service = app(ContractTerminationService::class);
    $terminated = Contract::factory()->create([
        'type' => ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'terminated_at' => now(),
        'hourly_rate' => 20,
    ]);
    $expired = Contract::factory()->create([
        'type' => ContractType::CDD,
        'start_date' => now()->subYears(2),
        'end_date' => now()->subDay(),
        'hourly_rate' => 20,
    ]);

    expect(fn () => $service->terminateCdd($terminated, now()))->toThrow(LogicException::class);
    expect(fn () => $service->terminateCdd($expired, now()))->toThrow(LogicException::class);
});

it('excludes a terminated CDD from the active scope', function () {
    $employee = Employee::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'hourly_rate' => 20,
    ]);

    app(ContractTerminationService::class)->terminateCdd(
        contract: $contract,
        terminationDate: now()->subDay(),
        reason: 'Rupture immédiate',
    );

    expect(Contract::query()->active()->whereKey($contract->id)->exists())->toBeFalse();
    expect($contract->fresh()->isActive())->toBeFalse();
});

it('makes a future-dated CDD termination inactive immediately', function () {
    $terminationDate = now()->addDays(10)->startOfDay();
    $contract = Contract::factory()->create([
        'type' => ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'hourly_rate' => 20,
    ]);

    app(ContractTerminationService::class)->terminateCdd(
        contract: $contract,
        terminationDate: $terminationDate,
    );

    $contract = $contract->fresh();

    expect($contract->isTerminated())->toBeTrue();
    expect($contract->isActive())->toBeFalse();
    expect(Contract::query()->active()->whereKey($contract->id)->exists())->toBeFalse();
});

it('calculates notice period for < 6 months tenure', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subMonths(3),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $noticeEndDate = $service->calculateNoticeEndDate($contract, now());

    // < 6 months: 2 days notice (prévenance légale)
    expect($noticeEndDate->format('d/m/Y'))->toBe(now()->copy()->addDays(2)->format('d/m/Y'));
});

it('calculates notice period for 6-24 months tenure (1 mois)', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subMonths(12),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $noticeEndDate = $service->calculateNoticeEndDate($contract, now());

    // 6-24 months: 1 mois calendar (date à date)
    expect($noticeEndDate->format('d/m/Y'))->toBe(now()->copy()->addMonthNoOverflow()->format('d/m/Y'));
});

it('calculates notice period for 2-5 years tenure (2 mois)', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(3),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $noticeEndDate = $service->calculateNoticeEndDate($contract, now());

    // 2-5 ans: 2 mois calendar
    expect($noticeEndDate->format('d/m/Y'))->toBe(now()->copy()->addMonthsNoOverflow(2)->format('d/m/Y'));
});

it('calculates notice period for 5-10 years tenure (4 mois)', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(7),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $noticeEndDate = $service->calculateNoticeEndDate($contract, now());

    // 5-10 ans: 4 mois calendar
    expect($noticeEndDate->format('d/m/Y'))->toBe(now()->copy()->addMonthsNoOverflow(4)->format('d/m/Y'));
});

it('calculates notice period for > 10 years tenure (8 mois)', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(12),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $noticeEndDate = $service->calculateNoticeEndDate($contract, now());

    // > 10 ans: 8 mois calendar
    expect($noticeEndDate->format('d/m/Y'))->toBe(now()->copy()->addMonthsNoOverflow(8)->format('d/m/Y'));
});

it('scope active excludes contracts with past notice_end_date', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
        'terminated_at' => now()->subMonth(),
        'notice_end_date' => now()->subWeek(),
    ]);

    $activeContracts = $employee->contracts()->active()->get();

    expect($activeContracts->isEmpty())->toBeTrue();
});

it('scope active includes terminated contracts during notice period', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
        'terminated_at' => now(),
        'notice_end_date' => now()->addMonth(),
    ]);

    $activeContracts = $employee->contracts()->active()->get();

    expect($activeContracts->isNotEmpty())->toBeTrue();
});

it('scope terminated only returns terminated contracts', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $terminatedContract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
        'terminated_at' => now()->subWeek(),
    ]);

    Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subMonth(),
        'hourly_rate' => 20,
        'terminated_at' => null,
    ]);

    $terminatedContracts = $employee->contracts()->terminated()->get();

    expect($terminatedContracts->count())->toBe(1);
    expect($terminatedContracts->first()->id)->toBe($terminatedContract->id);
});

it('termination keeps role during notice period', function () {
    $roleName = 'Conducteur de travaux';
    Role::create(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole($roleName);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'job_title' => $roleName,
        'start_date' => now()->subYear(),
        'hourly_rate' => 20,
    ]);

    expect($user->fresh()->hasRole($roleName))->toBeTrue();

    $service = app(ContractTerminationService::class);
    $service->terminate(
        contract: $contract,
        type: TerminationType::DEMISSION,
        reason: 'Démission volontaire',
    );

    // Role is kept during notice period (notice_end_date is in the future)
    expect($user->fresh()->hasRole($roleName))->toBeTrue();
});

it('can terminate with rupture conventionnelle type', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(2),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $terminated = $service->terminate(
        contract: $contract,
        type: TerminationType::RUPTURE_CONVENTIONNELLE,
        amount: 5000.00,
    );

    expect($terminated->termination_type)->toBe(TerminationType::RUPTURE_CONVENTIONNELLE);
    expect($terminated->termination_amount)->toBe('5000.00');
    expect($terminated->isTerminated())->toBeTrue();
});

it('can terminate with retraite type', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(30),
        'hourly_rate' => 30,
    ]);

    $service = app(ContractTerminationService::class);
    $terminated = $service->terminate(
        contract: $contract,
        type: TerminationType::RETRAITE,
    );

    expect($terminated->termination_type)->toBe(TerminationType::RETRAITE);
    expect($terminated->isTerminated())->toBeTrue();
});

it('returns correct notice months', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => ContractType::CDI,
        'start_date' => now()->subYears(3),
        'hourly_rate' => 20,
    ]);

    $service = app(ContractTerminationService::class);
    $months = $service->getNoticeMonths($contract, now());

    expect($months)->toBe(2);
});

it('getArticle returns correct prefix', function () {
    expect(TerminationType::LICENCIEMENT->getArticle())->toBe('votre ');
    expect(TerminationType::RETRAITE->getArticle())->toBe('votre départ à la ');
    expect(TerminationType::DEMISSION->getArticle())->toBe('votre ');
});
