<?php

use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\AbsenceType;
use App\Filament\Salarie\Resources\ContractResource;
use App\Filament\Salarie\Widgets\LeaveBalanceWidget;
use App\Filament\Salarie\Widgets\PayslipDownloadWidget;
use App\Models\Paie\Payslip;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\RH\LeaveBalanceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
    $this->actingAs($this->user);
});

describe('LeaveBalanceWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new LeaveBalanceWidget;
        expect($widget)->toBeInstanceOf(LeaveBalanceWidget::class);
    });

    test('le service calcule le solde CP avec contrat', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1));

        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::create(2024, 1, 1),
        ]));
        $this->employee->load('currentContract');

        $service = app(LeaveBalanceService::class);

        // 12.5 acquired (Jan-Jun = 5 months * 2.5) - 0 consumed = 12.5
        $balance = $service->getBalance($this->employee, AbsenceType::PAID_LEAVE);
        expect($balance)->toEqual(12.5);

        Carbon::setTestNow();
    });

    test('le service soustrait les jours consommés', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1));

        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::create(2024, 1, 1),
        ]));
        $this->employee->load('currentContract');

        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::create(2026, 5, 13),
            'end_date' => Carbon::create(2026, 5, 15),
            'is_paid' => true,
        ]);

        $service = app(LeaveBalanceService::class);

        // 12.5 - 3 = 9.5
        $balance = $service->getBalance($this->employee, AbsenceType::PAID_LEAVE);
        expect($balance)->toEqual(9.5);

        Carbon::setTestNow();
    });

    test('le service retourne 0 pour les types sans taux', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1));

        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::create(2024, 1, 1),
        ]));
        $this->employee->load('currentContract');

        $service = app(LeaveBalanceService::class);

        $balance = $service->getBalance($this->employee, AbsenceType::UNPAID_LEAVE);
        expect($balance)->toEqual(0.0);

        Carbon::setTestNow();
    });

    test('compte les jours maladie cette année', function () {
        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::SICK_LEAVE,
            'start_date' => Carbon::create(2026, 3, 2),  // Lundi
            'end_date' => Carbon::create(2026, 3, 6),    // Vendredi
            'is_paid' => false,
        ]);

        $sickDays = Abscence::where('employee_id', $this->employee->id)
            ->where('type', AbsenceType::SICK_LEAVE)
            ->whereYear('start_date', now()->year)
            ->get()
            ->sum(function (Abscence $absence) {
                $days = 0;
                $period = CarbonPeriod::create($absence->start_date, $absence->end_date);
                foreach ($period as $date) {
                    if (! $date->isWeekend()) {
                        $days++;
                    }
                }

                return $days;
            });

        expect($sickDays)->toEqual(5);
    });
});

describe('PayslipDownloadWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new PayslipDownloadWidget;
        expect($widget)->toBeInstanceOf(PayslipDownloadWidget::class);
    });

    test('getPayslips affiche les 3 derniers bulletins', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'period' => '2026-05',
            'status' => PayslipStatus::PAID,
            'net_paid' => 1350.00,
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'period' => '2026-04',
            'status' => PayslipStatus::VALIDATED,
            'net_paid' => 1280.00,
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'period' => '2026-03',
            'status' => PayslipStatus::PAID,
            'net_paid' => 1400.00,
        ]);

        $widget = new PayslipDownloadWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(3)
            ->and($payslips->first()->period)->toBe('2026-05')
            ->and($payslips->last()->period)->toBe('2026-03');
    });

    test('getPayslips n\'affiche que VALIDATED ou PAID', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'period' => '2026-05',
            'status' => PayslipStatus::PAID,
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'period' => '2026-04',
            'status' => PayslipStatus::DRAFT,
        ]);

        $widget = new PayslipDownloadWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(1);
    });

    test('getPayslips retourne une collection vide si pas de bulletins', function () {
        $widget = new PayslipDownloadWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toBeEmpty();
    });

    test('getPayslipsCount compte correctement', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::VALIDATED,
        ]);

        $widget = new PayslipDownloadWidget;

        expect($widget->getPayslipsCount())->toBe(2);
    });

    test('getPayslips ne montre que les bulletins de l\'employé', function () {
        $otherEmployee = Employee::factory()->create();
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
        ]);
        Payslip::factory()->create([
            'employee_id' => $otherEmployee->id,
            'status' => PayslipStatus::PAID,
        ]);

        $widget = new PayslipDownloadWidget;

        expect($widget->getPayslips())->toHaveCount(1)
            ->and($widget->getPayslipsCount())->toBe(1);
    });
});

describe('ContractResource', function () {

    test('ne montre que les contrats de l\'employé', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]));
        $otherEmployee = Employee::factory()->create();
        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $otherEmployee->id,
        ]));

        $contracts = ContractResource::getEloquentQuery()->get();

        expect($contracts)->toHaveCount(1)
            ->and($contracts->first()->id)->toBe($contract->id);
    });

    test('ne permet pas la création', function () {
        expect(ContractResource::canCreate())->toBeFalse();
    });

    test('ne permet pas l\'édition', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]));

        expect(ContractResource::canEdit($contract))->toBeFalse();
    });

    test('ne permet pas la suppression', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]));

        expect(ContractResource::canDelete($contract))->toBeFalse();
    });

    test('calcule le salaire mensuel estimé', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'hourly_rate' => 15.50,
            'weekly_hours' => 35.00,
        ]));

        // 15.50 * 35 * 4 = 2170.00
        expect($contract->getSalary())->toEqual(2170.00);
    });

    test('les contrats sont triés par date de début décroissante', function () {
        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::create(2024, 1, 1),
        ]));
        Contract::withoutEvents(fn () => Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::create(2026, 1, 1),
        ]));

        $contracts = ContractResource::getEloquentQuery()->get();

        expect($contracts->first()->start_date->year)->toBe(2026)
            ->and($contracts->last()->start_date->year)->toBe(2024);
    });
});
