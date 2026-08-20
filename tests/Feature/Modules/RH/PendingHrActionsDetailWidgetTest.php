<?php

use App\Enums\RH\AbsenceType;
use App\Enums\RH\ExpenseReportStatus;
use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
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

function widgetDetails(PendingHrActionsDetailWidget $widget): array
{
    $method = new ReflectionMethod($widget, 'getDetails');
    $method->setAccessible(true);

    return $method->invoke($widget);
}

it('renders the pending HR actions widget with a submitted expense report', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $employee = Employee::factory()->create();
    $report = ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'status' => ExpenseReportStatus::SUBMITTED,
        'total_amount' => 150,
    ]);

    Livewire::test(PendingHrActionsDetailWidget::class)->assertOk();

    $details = widgetDetails(new PendingHrActionsDetailWidget);
    expect($details)->toHaveCount(1);

    $detail = $details[0];
    expect($detail->getLabel())->toBe('Note de Frais - '.$employee->first_name.' '.$employee->last_name);
    expect($detail->getValue())->toBe('150,00 €');
    expect($detail->getUrl())->toBe(ExpenseReportResource::getUrl('edit', ['record' => $report]));
});

it('renders the pending HR actions widget with an unpaid absence', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $employee = Employee::factory()->create();
    $absence = Abscence::factory()->create([
        'employee_id' => $employee->id,
        'type' => AbsenceType::SICK_LEAVE,
        'start_date' => Carbon::now()->startOfDay(),
        'end_date' => Carbon::now()->addDay()->endOfDay(),
        'is_paid' => false,
    ]);

    Livewire::test(PendingHrActionsDetailWidget::class)->assertOk();

    $details = widgetDetails(new PendingHrActionsDetailWidget);
    expect($details)->toHaveCount(1);

    $detail = $details[0];
    expect($detail->getLabel())->toBe('Absence - '.$employee->first_name.' '.$employee->last_name);
    expect($detail->getValue())->toBe($absence->start_date->format('d/m/Y'));
    expect($detail->getUrl())->toBe(EmployeeResource::getUrl('edit', ['record' => $employee]));
});
