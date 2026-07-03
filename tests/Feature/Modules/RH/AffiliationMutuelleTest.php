<?php

use App\Models\RH\Employee;
use App\Models\RH\Contract;
use App\Models\Core\Company;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

beforeEach(function () {
    Company::factory()->create([
        'legal_name' => 'Batistack Test',
        'siret' => '12345678901234',
        'address' => '1 rue Test',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
});

it('auto-generates the affiliation form when onboarding is completed', function () {
    $employee = Employee::factory()->create([
        'onboarding_completed' => false,
    ]);

    // Create a dummy PDF file
    $dummyPath = 'documents/rh/onboarding/dummy_affiliation.pdf';
    Storage::disk('public')->put($dummyPath, 'fake pdf content');

    // Mock the service
    $mock = Mockery::mock(\App\Services\RH\RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generateAffiliationMutuelle')
        ->once()
        ->andReturn($dummyPath);
    app()->instance(\App\Services\RH\RHDocumentService::class, $mock);

    // Act: Mark onboarding as completed
    $employee->onboarding_completed = true;
    $employee->save();

    // Assert: Media is attached
    $media = $employee->getFirstMedia('rh_documents');
    
    expect($media)->not->toBeNull()
        ->and($media->file_name)->toContain('dummy_affiliation');
});

it('does not generate the affiliation form if onboarding is already completed', function () {
    $employee = Employee::factory()->create([
        'onboarding_completed' => true,
    ]);

    $mock = Mockery::mock(\App\Services\RH\RHDocumentService::class)->makePartial();
    $mock->shouldNotReceive('generateAffiliationMutuelle');
    app()->instance(\App\Services\RH\RHDocumentService::class, $mock);

    // Act: update something else
    $employee->first_name = 'Jean-Claude';
    $employee->save();
});
