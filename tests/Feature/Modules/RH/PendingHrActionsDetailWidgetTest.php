<?php

use App\Enums\RH\AbsenceType;
use App\Enums\RH\ExpenseReportStatus;
use App\Filament\RH\Widgets\PendingHrActionsDetailWidget;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('rh'));
});

it('renders the pending HR actions widget with a submitted expense report', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $employee = Employee::factory()->create();
    ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'status' => ExpenseReportStatus::SUBMITTED,
        'total_amount' => 150,
    ]);

    Livewire::test(PendingHrActionsDetailWidget::class)->assertOk();
});

it('renders the pending HR actions widget with an absence present', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $employee = Employee::factory()->create();
    Abscence::factory()->create([
        'employee_id' => $employee->id,
        'type' => AbsenceType::SICK_LEAVE,
        'start_date' => Carbon::now()->startOfDay(),
        'end_date' => Carbon::now()->addDay()->endOfDay(),
    ]);

    Livewire::test(PendingHrActionsDetailWidget::class)->assertOk();
});
