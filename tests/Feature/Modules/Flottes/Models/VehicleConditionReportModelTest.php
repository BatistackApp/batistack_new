<?php

use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\VehicleConditionReport;

test('scope checkIn filtre rapports check-in', function () {
    VehicleConditionReport::factory()->count(2)->create([
        'type' => ConditionReportType::CHECK_IN,
    ]);
    VehicleConditionReport::factory()->count(1)->create([
        'type' => ConditionReportType::CHECK_OUT,
    ]);

    $checkIns = VehicleConditionReport::checkIn()->get();

    expect($checkIns)->toHaveCount(2);
});

test('scope checkOut filtre rapports check-out', function () {
    VehicleConditionReport::factory()->count(1)->create([
        'type' => ConditionReportType::CHECK_IN,
    ]);
    VehicleConditionReport::factory()->count(2)->create([
        'type' => ConditionReportType::CHECK_OUT,
    ]);

    $checkOuts = VehicleConditionReport::checkOut()->get();

    expect($checkOuts)->toHaveCount(2);
});

test('relation assignment charge affectation', function () {
    $assignment = \App\Models\Flottes\VehicleAssignment::factory()->create();
    $report = VehicleConditionReport::factory()->create([
        'vehicle_assignment_id' => $assignment->id,
    ]);

    $report->load('assignment');

    expect($report->assignment->id)->toBe($assignment->id);
});

test('méthode validateChecksum vérifie intégrité', function () {
    $report = VehicleConditionReport::factory()->create([
        'signature_checksum' => 'abc123def456',
    ]);

    expect($report->validateChecksum('abc123def456'))->toBeTrue()
        ->and($report->validateChecksum('wrongchecksum'))->toBeFalse();
});
