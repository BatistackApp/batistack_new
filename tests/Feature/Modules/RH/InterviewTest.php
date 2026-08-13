<?php

use App\Models\RH\Interview;
use App\Models\RH\Employee;
use App\Models\User;
use App\Enums\RH\InterviewType;
use App\Enums\RH\InterviewStatus;
use App\Services\RH\InterviewPdfService;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates an interview with required fields', function () {
    $employee = Employee::factory()->create();
    $manager = User::factory()->create();

    $interview = Interview::create([
        'employee_id' => $employee->id,
        'manager_id' => $manager->id,
        'type' => InterviewType::ANNUEL,
        'status' => InterviewStatus::PLANIFIE,
        'scheduled_at' => now()->addDays(5),
    ]);

    expect($interview->id)->not->toBeNull()
        ->and($interview->type)->toBe(InterviewType::ANNUEL);

    $this->assertDatabaseHas('interviews', [
        'employee_id' => $employee->id,
        'manager_id' => $manager->id,
    ]);
});

it('can store evaluation grid as json array', function () {
    $interview = Interview::factory()->create([
        'evaluation_grid' => [
            ['question' => 'Objectif 1', 'answer' => 'Atteint'],
            ['question' => 'Objectif 2', 'answer' => 'Partiellement atteint'],
        ]
    ]);

    expect($interview->evaluation_grid)->toBeArray()
        ->and($interview->evaluation_grid)->toHaveCount(2)
        ->and($interview->evaluation_grid[0]['answer'])->toBe('Atteint');
});

it('can store signatures as text', function () {
    $interview = Interview::factory()->create([
        'employee_signature' => 'data:image/svg+xml;base64,...',
        'manager_signature' => 'data:image/svg+xml;base64,...',
    ]);

    expect($interview->employee_signature)->toStartWith('data:image/svg+xml')
        ->and($interview->manager_signature)->toStartWith('data:image/svg+xml');
});
