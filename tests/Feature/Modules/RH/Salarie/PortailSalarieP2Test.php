<?php

use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\AbsenceType;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Filament\Salarie\Widgets\ActivityFeedWidget;
use App\Filament\Salarie\Widgets\TimeEntryRecapWidget;
use App\Models\Paie\Payslip;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
    $this->actingAs($this->user);
});

describe('TimeEntryRecapWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new TimeEntryRecapWidget;
        expect($widget)->toBeInstanceOf(TimeEntryRecapWidget::class);
    });

    test('getFilterOptions retourne 3 options', function () {
        $widget = new TimeEntryRecapWidget;
        $options = $widget->getFilterOptions();

        expect($options)->toHaveCount(3);
        expect($options)->toHaveKey('week');
        expect($options)->toHaveKey('month');
        expect($options)->toHaveKey('year');
    });

    test('getSummary retourne des zéros sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new TimeEntryRecapWidget;
        $summary = $widget->getSummary();

        expect($summary['total_hours'])->toEqual(0);
        expect($summary['approved_hours'])->toEqual(0);
        expect($summary['pending_count'])->toEqual(0);
        expect($summary['entry_count'])->toEqual(0);
    });

    test('getSummary comptabilise les heures du mois en cours', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 15));

        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 8, 5),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 8, 6),
                'hours' => 4.0,
                'type' => TimeEntryType::OVERTIME_25,
                'status' => TimeEntryStatus::SUBMITTED,
            ]);
        });

        $widget = new TimeEntryRecapWidget;
        $summary = $widget->getSummary();

        expect($summary['total_hours'])->toEqual(12.0);
        expect($summary['approved_hours'])->toEqual(8.0);
        expect($summary['pending_count'])->toEqual(1);
        expect($summary['entry_count'])->toEqual(2);

        Carbon::setTestNow();
    });

    test('getTimeEntries retourne les pointages du mois', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 15));

        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 8, 5),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new TimeEntryRecapWidget;
        $entries = $widget->getTimeEntries();

        expect($entries)->toHaveCount(1);
        expect($entries->first()->hours)->toEqual('8.00');

        Carbon::setTestNow();
    });

    test('getTimeEntries filtre par semaine', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 20)); // Wednesday

        TimeEntry::withoutEvents(function () {
            // This week
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 8, 18), // Monday
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
            // Last month
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 7, 15),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new TimeEntryRecapWidget;
        $widget->setFilter('week');
        $entries = $widget->getTimeEntries();

        expect($entries)->toHaveCount(1);

        Carbon::setTestNow();
    });

    test('getTimeEntries filtre par année', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 15));

        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 3, 10),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2025, 12, 15),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new TimeEntryRecapWidget;
        $widget->setFilter('year');
        $entries = $widget->getTimeEntries();

        expect($entries)->toHaveCount(1); // Only 2026 entry

        Carbon::setTestNow();
    });

    test('getTimeEntries retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new TimeEntryRecapWidget;
        $entries = $widget->getTimeEntries();

        expect($entries)->toBeEmpty();
    });

    test('getTimeEntries retourne au maximum 10 entrées', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 15));

        TimeEntry::withoutEvents(function () {
            for ($i = 0; $i < 15; $i++) {
                TimeEntry::factory()->create([
                    'employee_id' => $this->employee->id,
                    'date' => Carbon::create(2026, 8, 1)->addDays($i),
                    'hours' => 8.0,
                    'type' => TimeEntryType::NORMAL,
                    'status' => TimeEntryStatus::APPROVED,
                ]);
            }
        });

        $widget = new TimeEntryRecapWidget;
        $entries = $widget->getTimeEntries();

        expect($entries)->toHaveCount(10);

        Carbon::setTestNow();
    });

    test('setFilter change le filtre actif', function () {
        $widget = new TimeEntryRecapWidget;
        expect($widget->activeFilter)->toEqual('month');

        $widget->setFilter('week');
        expect($widget->activeFilter)->toEqual('week');

        $widget->setFilter('year');
        expect($widget->activeFilter)->toEqual('year');
    });

    test('getSummary filtre correctement par semaine', function () {
        Carbon::setTestNow(Carbon::create(2026, 8, 20)); // Wednesday

        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 8, 18), // Monday this week
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => Carbon::create(2026, 7, 15),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new TimeEntryRecapWidget;
        $widget->setFilter('week');
        $summary = $widget->getSummary();

        expect($summary['total_hours'])->toEqual(8.0);
        expect($summary['entry_count'])->toEqual(1);

        Carbon::setTestNow();
    });
});

describe('ActivityFeedWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new ActivityFeedWidget;
        expect($widget)->toBeInstanceOf(ActivityFeedWidget::class);
    });

    test('loadActivities retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        expect($widget->activities)->toBeEmpty();
    });

    test('loadActivities inclut les pointages récents', function () {
        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => now()->subDay(),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        $timeEntryActivities = collect($widget->activities)->filter(
            fn ($a) => str_contains($a['label'], 'Pointage')
        );

        expect($timeEntryActivities->count())->toBeGreaterThanOrEqual(1);
    });

    test('loadActivities inclut les absences récentes', function () {
        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeek()->addDays(2),
            'is_paid' => true,
        ]);

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        $absenceActivities = collect($widget->activities)->filter(
            fn ($a) => str_contains($a['label'], 'Congé') || str_contains($a['label'], 'RTT') || str_contains($a['label'], 'Maladie')
        );

        expect($absenceActivities->count())->toBeGreaterThanOrEqual(1);
    });

    test('loadActivities inclut les bulletins récents', function () {
        Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => PayslipStatus::PAID,
            'period' => '2026-07',
        ]);

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        $payslipActivities = collect($widget->activities)->filter(
            fn ($a) => str_contains($a['label'], 'Bulletin')
        );

        expect($payslipActivities->count())->toBeGreaterThanOrEqual(1);
    });

    test('loadActivities trie par date décroissante', function () {
        TimeEntry::withoutEvents(function () {
            TimeEntry::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => now()->subDays(5),
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::RTT,
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(10),
            'is_paid' => true,
        ]);

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        if (count($widget->activities) >= 2) {
            $first = $widget->activities[0]['date'];
            $second = $widget->activities[1]['date'];
            expect($first->greaterThanOrEqualTo($second))->toBeTrue();
        }
    });

    test('loadActivities retourne au maximum 10 activités', function () {
        TimeEntry::withoutEvents(function () {
            for ($i = 0; $i < 5; $i++) {
                TimeEntry::factory()->create([
                    'employee_id' => $this->employee->id,
                    'date' => now()->subDays($i),
                    'hours' => 8.0,
                    'type' => TimeEntryType::NORMAL,
                    'status' => TimeEntryStatus::APPROVED,
                ]);
            }
        });

        for ($i = 0; $i < 5; $i++) {
            Abscence::create([
                'employee_id' => $this->employee->id,
                'type' => AbsenceType::PAID_LEAVE,
                'start_date' => Carbon::now()->addDays(20 + $i),
                'end_date' => Carbon::now()->addDays(20 + $i),
                'is_paid' => true,
            ]);
        }

        $widget = new ActivityFeedWidget;
        $widget->loadActivities();

        expect(count($widget->activities))->toBeLessThanOrEqual(10);
    });
});
