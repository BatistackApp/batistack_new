<?php

use App\Jobs\OptimizeTechnicianRouteJob;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\Interventions\RouteOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;

uses(RefreshDatabase::class);

it('optimizes route and sends success notification', function () {
    $technicien = Employee::factory()->create();
    $user = User::factory()->create();
    $date = '2026-08-03';

    // Mock the service
    $mockService = Mockery::mock(RouteOptimizationService::class);
    $mockService->shouldReceive('optimizeForTechnician')
        ->once()
        ->with(Mockery::on(function ($arg) use ($technicien) {
            return $arg->id === $technicien->id;
        }), $date)
        ->andReturn([
            'success' => true,
            'message' => 'Test message',
            'interventions_count' => 3,
        ]);

    // Bind mock to container
    $this->app->instance(RouteOptimizationService::class, $mockService);

    // Run the job
    $job = new OptimizeTechnicianRouteJob($technicien->id, $date, $user->id);
    $job->handle(app(RouteOptimizationService::class));

    // Assert database has notification for the user
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
    ]);

    $notification = DatabaseNotification::where('notifiable_id', $user->id)->first();
    expect($notification->data['title'] ?? '')->toContain('Optimisation terminée');
});

it('handles optimization failure and sends error notification', function () {
    $technicien = Employee::factory()->create();
    $user = User::factory()->create();
    $date = '2026-08-03';

    // Mock the service
    $mockService = Mockery::mock(RouteOptimizationService::class);
    $mockService->shouldReceive('optimizeForTechnician')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Error message',
        ]);

    $this->app->instance(RouteOptimizationService::class, $mockService);

    $job = new OptimizeTechnicianRouteJob($technicien->id, $date, $user->id);
    $job->handle(app(RouteOptimizationService::class));

    $notification = DatabaseNotification::where('notifiable_id', $user->id)->first();
    expect($notification->data['title'] ?? '')->toContain('Erreur');
    expect($notification->data['body'] ?? '')->toContain('Error message');
});

it('aborts if technician is not found', function () {
    $date = '2026-08-03';

    // Mock the service to ensure it's NEVER called
    $mockService = Mockery::mock(RouteOptimizationService::class);
    $mockService->shouldNotReceive('optimizeForTechnician');

    $this->app->instance(RouteOptimizationService::class, $mockService);

    // Pass a non-existent technician ID
    $job = new OptimizeTechnicianRouteJob(99999, $date);
    $job->handle(app(RouteOptimizationService::class));

    // Test passes if no exception is thrown and optimizeForTechnician was not called
    expect(true)->toBeTrue();
});
