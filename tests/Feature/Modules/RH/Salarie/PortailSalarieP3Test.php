<?php

use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\AbsenceType;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Filament\Salarie\Widgets\DigiposteWidget;
use App\Filament\Salarie\Widgets\PlanningCalendarWidget;
use App\Models\Paie\Payslip;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
    $this->actingAs($this->user);
});

describe('PlanningCalendarWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new PlanningCalendarWidget;
        expect($widget)->toBeInstanceOf(PlanningCalendarWidget::class);
    });

    test('getEvents retourne les absences dans la plage', function () {
        $absence = Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::create(2026, 9, 1),
            'end_date' => Carbon::create(2026, 9, 3),
            'is_paid' => true,
        ]);

        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2026, 9, 1),
            'end' => CarbonImmutable::create(2026, 9, 30),
        ]);

        $absenceEvents = $events->filter(fn ($e) => str_contains($e->getTitle(), 'Congé'));
        expect($absenceEvents->count())->toBeGreaterThanOrEqual(1);
    });

    test('getEvents retourne les pointages dans la plage', function () {
        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 9, 5),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2026, 9, 1),
            'end' => CarbonImmutable::create(2026, 9, 30),
        ]);

        $timeEntryEvents = $events->filter(fn ($e) => str_contains($e->getTitle(), '8'));
        expect($timeEntryEvents->count())->toBeGreaterThanOrEqual(1);
    });

    test('getEvents est scopé à l\'employé authentifié', function () {
        $otherEmployee = Employee::factory()->create();

        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::create(2026, 9, 1),
            'end_date' => Carbon::create(2026, 9, 3),
            'is_paid' => true,
        ]);

        Abscence::create([
            'employee_id' => $otherEmployee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::create(2026, 9, 5),
            'end_date' => Carbon::create(2026, 9, 7),
            'is_paid' => true,
        ]);

        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2026, 9, 1),
            'end' => CarbonImmutable::create(2026, 9, 30),
        ]);

        // Should only have events for the authenticated employee
        foreach ($events as $event) {
            expect($event->getTitle())->not->toContain($otherEmployee->first_name);
        }
    });

    test('getEvents retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2026, 9, 1),
            'end' => CarbonImmutable::create(2026, 9, 30),
        ]);

        expect($events)->toBeEmpty();
    });

    test('getEvents retourne vide pour une plage sans données', function () {
        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2020, 1, 1),
            'end' => CarbonImmutable::create(2020, 1, 31),
        ]);

        expect($events)->toBeEmpty();
    });

    test('getEvents retourne les absences et pointages mélangés par date', function () {
        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::RTT,
            'start_date' => Carbon::create(2026, 9, 2),
            'end_date' => Carbon::create(2026, 9, 2),
            'is_paid' => true,
        ]);

        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 9, 3),
                'hours' => 7.5,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new PlanningCalendarWidget;
        $events = $widget->getEvents([
            'start' => CarbonImmutable::create(2026, 9, 1),
            'end' => CarbonImmutable::create(2026, 9, 30),
        ]);

        expect($events->count())->toBeGreaterThanOrEqual(2);
    });
});

describe('DigiposteWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new DigiposteWidget;
        expect($widget)->toBeInstanceOf(DigiposteWidget::class);
    });

    test('getPayslips retourne les bulletins avec pdf_path', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'period' => '2026-07',
            'pdf_path' => 'documents/payslips/test.pdf',
        ]);

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(1);
        expect($payslips->first()->period)->toEqual('2026-07');
    });

    test('getPayslips exclut les bulletins sans pdf_path', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::VALIDATED,
            'period' => '2026-07',
            'pdf_path' => null,
        ]);

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(0);
    });

    test('getPayslips exclut les brouillons', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::DRAFT,
            'period' => '2026-07',
            'pdf_path' => 'documents/payslips/test.pdf',
        ]);

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(0);
    });

    test('getPayslips retourne au maximum 5 bulletins', function () {
        for ($i = 0; $i < 7; $i++) {
            Payslip::factory()->create([
                'employee_id' => $this->employee->id,
                'status' => PayslipStatus::PAID,
                'period' => '2026-'.str_pad(7 - $i, 2, '0', STR_PAD_LEFT),
                'pdf_path' => "documents/payslips/test_{$i}.pdf",
            ]);
        }

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toHaveCount(5);
    });

    test('getPayslips retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips)->toBeEmpty();
    });

    test('getRhDocuments retourne les documents media', function () {
        $this->employee->addMediaFromString('test doc content')
            ->usingName('Attestation salaire')
            ->usingFileName('attestation.pdf')
            ->toMediaCollection('rh_documents');

        $widget = new DigiposteWidget;
        $docs = $widget->getRhDocuments();

        expect($docs)->toHaveCount(1);
        expect($docs->first()->name)->toEqual('Attestation salaire');
    });

    test('getRhDocuments retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new DigiposteWidget;
        $docs = $widget->getRhDocuments();

        expect($docs)->toBeEmpty();
    });

    test('getDocumentsTotal comptabilise les documents', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'pdf_path' => 'documents/payslips/test1.pdf',
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'pdf_path' => 'documents/payslips/test2.pdf',
        ]);

        $this->employee->addMediaFromString('doc content')
            ->usingName('Test doc')
            ->usingFileName('test.pdf')
            ->toMediaCollection('rh_documents');

        $widget = new DigiposteWidget;
        $total = $widget->getDocumentsTotal();

        expect($total)->toEqual(3); // 2 payslips + 1 RH doc
    });

    test('formatSize formate correctement les tailles', function () {
        expect(DigiposteWidget::formatSize(0))->toEqual('0 o');
        expect(DigiposteWidget::formatSize(null))->toEqual('0 o');
        expect(DigiposteWidget::formatSize(1024))->toEqual('1 Ko');
        expect(DigiposteWidget::formatSize(1048576))->toEqual('1 Mo');
        expect(DigiposteWidget::formatSize(1536))->toEqual('1.5 Ko');
    });

    test('getPayslips trie par période décroissante', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'period' => '2026-05',
            'pdf_path' => 'documents/payslips/test1.pdf',
        ]);
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'period' => '2026-07',
            'pdf_path' => 'documents/payslips/test2.pdf',
        ]);

        $widget = new DigiposteWidget;
        $payslips = $widget->getPayslips();

        expect($payslips->first()->period)->toEqual('2026-07');
        expect($payslips->last()->period)->toEqual('2026-05');
    });
});
