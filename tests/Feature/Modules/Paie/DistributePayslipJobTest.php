<?php

use App\Jobs\Paie\DistributePayslipJob;
use App\Mail\Paie\PayslipAvailableMail;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('distributes payslip via mail and notification', function () {
    Mail::fake();
    Notification::fake();

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'email' => 'employee@test.com'
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'period' => '2026-07'
    ]);

    $job = new DistributePayslipJob($payslip);
    $job->handle();

    Mail::assertSent(PayslipAvailableMail::class, function ($mail) use ($employee) {
        return $mail->hasTo($employee->email);
    });
});
