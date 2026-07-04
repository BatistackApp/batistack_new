<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RH\Employee;
use App\Models\RH\Contract;
use App\Services\RH\LeaveBalanceService;
use App\Enums\RH\AbsenceType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

Carbon::setTestNow(Carbon::create(2026, 6, 1, 0, 0, 0));

$employee = Employee::factory()->create();
$contract = Contract::withoutEvents(function () use ($employee) {
    return Contract::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => Carbon::create(2024, 1, 1, 0, 0, 0),
    ]);
});

$employee->load('currentContract');
echo 'Contract: ' . ($employee->currentContract ? 'FOUND' : 'NULL') . "\n";
if ($employee->currentContract) {
    echo 'Contract start date: ' . $employee->currentContract->start_date . "\n";
}

$service = new LeaveBalanceService;
echo 'Res1: ' . $service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE) . "\n";

$contract2 = Contract::withoutEvents(function () use ($employee) {
    return Contract::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => Carbon::create(2026, 4, 1, 0, 0, 0),
    ]);
});
$employee->load('currentContract');
echo 'Contract2 start date: ' . $employee->currentContract->start_date . "\n";
echo 'Res2: ' . $service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE) . "\n";

DB::rollBack();
