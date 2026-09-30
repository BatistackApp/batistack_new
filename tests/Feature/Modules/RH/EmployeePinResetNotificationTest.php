<?php

use App\Models\RH\Employee;
use App\Notifications\RH\EmployeePinResetNotification;

test('les canaux mail et database sont utilisés', function () {
    $employee = Employee::factory()->create();

    expect((new EmployeePinResetNotification('1234'))->via($employee))
        ->toBe(['mail', 'database']);
});

test('le mail contient le PIN provisoire et le rappel de changement', function () {
    $employee = Employee::factory()->create();

    $mail = (new EmployeePinResetNotification('0123'))->toMail($employee);
    $content = implode(' ', $mail->introLines);

    expect($mail->subject)->toContain('code PIN provisoire')
        ->and($content)->toContain('0123')
        ->and($content)->toContain('Mon Profil')
        ->and($content)->toContain('provisoire');
});

test('la notification database rappelle le changement de PIN', function () {
    $employee = Employee::factory()->create();

    $data = (new EmployeePinResetNotification('1234'))->toDatabase($employee);

    expect($data)->toHaveKeys(['title', 'body', 'icon', 'color'])
        ->and($data['title'])->toContain('Code PIN provisoire')
        ->and($data['body'])->toContain('Mon Profil');
});

test('le mail s\'adresse au salarié par son nom complet', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);

    $mail = (new EmployeePinResetNotification('1234'))->toMail($employee);

    expect($mail->greeting)->toContain('Jean Dupont');
});
