<?php

use App\Http\Middleware\EnsureUserIsChefDeChantier;
use App\Http\Middleware\EnsureUserIsEmployee;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Http\Request;

test('employee middleware redirects users without an employee profile to the employee login', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_employee' => true]);
    auth()->login($user);

    $response = app(EnsureUserIsEmployee::class)->handle(
        Request::create('/salarie'),
        fn () => response('next')
    );

    expect($response->headers->get('Location'))->toBe(route('filament.salarie.auth.login'));
    expect(auth()->check())->toBeFalse();
});

test('employee middleware redirects inactive employees to the employee login', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_employee' => true]);
    Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => false,
    ]);
    auth()->login($user);

    $response = app(EnsureUserIsEmployee::class)->handle(
        Request::create('/salarie'),
        fn () => response('next')
    );

    expect($response->headers->get('Location'))->toBe(route('filament.salarie.auth.login'));
    expect(auth()->check())->toBeFalse();
});

test('an active employee with the chef de chantier title is authorized', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_employee' => true]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);
    \App\Models\RH\Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    auth()->login($user);

    $response = app(EnsureUserIsChefDeChantier::class)->handle(
        Request::create('/terrain'),
        fn () => response('next')
    );

    expect($response->getContent())->toBe('next');
    expect(auth()->check())->toBeTrue();
});

test('the former hard-coded admin email does not bypass chef de chantier authorization', function () {
    $user = User::factory()->create([
        'email' => 'admin@admin.com',
        'is_admin' => false,
        'is_employee' => true,
    ]);
    Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);
    auth()->login($user);

    $response = app(EnsureUserIsChefDeChantier::class)->handle(
        Request::create('/terrain'),
        fn () => response('next')
    );

    expect($response->headers->get('Location'))->toBe(route('filament.terrain.auth.login'));
    expect(auth()->check())->toBeFalse();
});

test('an active administrator with an employee profile can access the terrain middleware', function () {
    $user = User::factory()->create(['is_admin' => true, 'is_employee' => true]);
    Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);
    auth()->login($user);

    $response = app(EnsureUserIsChefDeChantier::class)->handle(
        Request::create('/terrain'),
        fn () => response('next')
    );

    expect($response->getContent())->toBe('next');
    expect(auth()->check())->toBeTrue();
});
