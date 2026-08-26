<?php

use App\Enums\RH\AbsenceType;
use App\Enums\RH\QualificationType;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Filament\Salarie\Resources\QualificationResource;
use App\Filament\Salarie\Widgets\MonthlyHoursRecapWidget;
use App\Filament\Salarie\Widgets\UpcomingAbsencesWidget;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
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

function callProtected(object $object, string $method, mixed ...$args): mixed
{
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);

    return $ref->invoke($object, ...$args);
}

describe('MonthlyHoursRecapWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new MonthlyHoursRecapWidget;
        expect($widget)->toBeInstanceOf(MonthlyHoursRecapWidget::class);
    });

    test('getData retourne des données vides sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        expect($data['labels'])->toBeArray()->toBeEmpty();
        expect($data['datasets'])->toBeArray()->toBeEmpty();
    });

    test('getData retourne 3 labels de mois', function () {
        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        expect($data['labels'])->toHaveCount(3);
        expect($data['labels'][2])->toEqual(now()->translatedFormat('M Y'));
    });

    test('getData inclut les datasets pour chaque type d\'heure', function () {
        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        expect($data['datasets'])->toHaveCount(count(TimeEntryType::cases()));
    });

    test('getData agrège correctement les heures du mois en cours', function () {
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
                'hours' => 2.0,
                'type' => TimeEntryType::OVERTIME_25,
                'status' => TimeEntryStatus::APPROVED,
            ]);
        });

        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        $normalDataset = collect($data['datasets'])->firstWhere('label', 'Heures Normales');
        expect($normalDataset['data'][2])->toEqual(8.0);

        $ot25Dataset = collect($data['datasets'])->firstWhere('label', 'Heures Sup. 25%');
        expect($ot25Dataset['data'][2])->toEqual(2.0);

        Carbon::setTestNow();
    });

    test('getType retourne bar', function () {
        $widget = new MonthlyHoursRecapWidget;
        expect(callProtected($widget, 'getType'))->toEqual('bar');
    });

    test('les heures hors mois courant sont à 0', function () {
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

        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        $normalDataset = collect($data['datasets'])->firstWhere('label', 'Heures Normales');
        expect($normalDataset['data'][0])->toEqual(0.0);
        expect($normalDataset['data'][1])->toEqual(0.0);

        Carbon::setTestNow();
    });

    test('seules les entrées approuvées sont comptées', function () {
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
                'hours' => 8.0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::DRAFT,
            ]);
        });

        $widget = new MonthlyHoursRecapWidget;
        $data = callProtected($widget, 'getData');

        $normalDataset = collect($data['datasets'])->firstWhere('label', 'Heures Normales');
        expect($normalDataset['data'][2])->toEqual(8.0);

        Carbon::setTestNow();
    });
});

describe('QualificationResource', function () {

    test('la resource peut être instanciée', function () {
        $resource = new QualificationResource;
        expect($resource)->toBeInstanceOf(QualificationResource::class);
    });

    test('canCreate retourne false', function () {
        expect(QualificationResource::canCreate())->toBeFalse();
    });

    test('canEdit retourne false', function () {
        $q = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect(QualificationResource::canEdit($q))->toBeFalse();
    });

    test('canDelete retourne false', function () {
        $q = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect(QualificationResource::canDelete($q))->toBeFalse();
    });

    test('getEloquentQuery scope par employee', function () {
        $otherEmployee = Employee::factory()->create();
        Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'type' => QualificationType::CACES,
        ]);
        Qualification::factory()->create([
            'employee_id' => $otherEmployee->id,
            'type' => QualificationType::CACES,
        ]);

        $query = QualificationResource::getEloquentQuery();
        $results = $query->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->employee_id)->toEqual($this->employee->id);
    });

    test('getEloquentQuery trie par obtained_at décroissant', function () {
        $old = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'obtained_at' => Carbon::create(2025, 1, 1),
        ]);
        $recent = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'obtained_at' => Carbon::create(2026, 6, 1),
        ]);

        $results = QualificationResource::getEloquentQuery()->get();

        expect($results->first()->id)->toEqual($recent->id);
    });

    test('le model Qualification a les bonnes méthodes statut', function () {
        $active = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'expires_at' => Carbon::now()->addMonths(6),
        ]);
        $expired = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'expires_at' => Carbon::now()->subMonth(),
        ]);
        $expiringSoon = Qualification::factory()->create([
            'employee_id' => $this->employee->id,
            'expires_at' => Carbon::now()->addDays(10),
        ]);

        expect($active->isActive())->toBeTrue();
        expect($active->isExpired())->toBeFalse();

        expect($expired->isActive())->toBeFalse();
        expect($expired->isExpired())->toBeTrue();

        expect($expiringSoon->isExpiringSoon())->toBeTrue();
    });
});

describe('UpcomingAbsencesWidget', function () {

    test('le widget peut être instancié', function () {
        $widget = new UpcomingAbsencesWidget;
        expect($widget)->toBeInstanceOf(UpcomingAbsencesWidget::class);
    });

    test('getAbsences retourne les absences futures', function () {
        $futureAbsence = Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeek()->addDays(2),
            'is_paid' => true,
        ]);

        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->subWeek()->addDays(2),
            'is_paid' => true,
        ]);

        $absences = (new UpcomingAbsencesWidget)->getAbsences();

        expect($absences)->toHaveCount(1);
        expect($absences->first()->id)->toEqual($futureAbsence->id);
    });

    test('getAbsences retourne au maximum 5 absences', function () {
        for ($i = 0; $i < 7; $i++) {
            Abscence::create([
                'employee_id' => $this->employee->id,
                'type' => AbsenceType::PAID_LEAVE,
                'start_date' => Carbon::now()->addDays($i * 10),
                'end_date' => Carbon::now()->addDays($i * 10 + 1),
                'is_paid' => true,
            ]);
        }

        $absences = (new UpcomingAbsencesWidget)->getAbsences();

        expect($absences)->toHaveCount(5);
    });

    test('getAbsences retourne vide sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $absences = (new UpcomingAbsencesWidget)->getAbsences();

        expect($absences)->toBeEmpty();
    });

    test('getAbsencesCount retourne le nombre correct', function () {
        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeek()->addDays(2),
            'is_paid' => true,
        ]);
        Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::RTT,
            'start_date' => Carbon::now()->addDays(20),
            'end_date' => Carbon::now()->addDays(20),
            'is_paid' => true,
        ]);

        $count = (new UpcomingAbsencesWidget)->getAbsencesCount();

        expect($count)->toEqual(2);
    });

    test('getAbsences trie par date croissante', function () {
        $later = Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::now()->addDays(30),
            'end_date' => Carbon::now()->addDays(32),
            'is_paid' => true,
        ]);
        $sooner = Abscence::create([
            'employee_id' => $this->employee->id,
            'type' => AbsenceType::RTT,
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(5),
            'is_paid' => true,
        ]);

        $absences = (new UpcomingAbsencesWidget)->getAbsences();

        expect($absences->first()->id)->toEqual($sooner->id);
        expect($absences->last()->id)->toEqual($later->id);
    });

    test('getAbsencesCount retourne 0 sans employé', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $count = (new UpcomingAbsencesWidget)->getAbsencesCount();

        expect($count)->toEqual(0);
    });
});
