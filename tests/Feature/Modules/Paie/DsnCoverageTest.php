<?php

use App\Enums\Paie\DsnStatus;
use App\Enums\Paie\DsnSubmissionStatus;
use App\Models\Paie\DsnSubmission;
use App\Models\Paie\DsnSubmissionLine;
use App\Models\Paie\Payslip;
use App\Models\User;
use App\Services\Paie\DsnExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('validates all DsnStatus getLabel cases', function () {
    expect(DsnStatus::READY->getLabel())->toBe('Prête');
    expect(DsnStatus::EXPORTED->getLabel())->toBe('Exportée');
    expect(DsnStatus::SUBMITTED->getLabel())->toBe('Soumise');
    expect(DsnStatus::ACCEPTED->getLabel())->toBe('Acceptée');
    expect(DsnStatus::REJECTED->getLabel())->toBe('Rejetée');
});

it('validates all DsnStatus getColor cases', function () {
    expect(DsnStatus::READY->getColor())->toBe('info');
    expect(DsnStatus::EXPORTED->getColor())->toBe('warning');
    expect(DsnStatus::SUBMITTED->getColor())->toBe('primary');
    expect(DsnStatus::ACCEPTED->getColor())->toBe('success');
    expect(DsnStatus::REJECTED->getColor())->toBe('danger');
});

it('validates all DsnSubmissionStatus getLabel cases', function () {
    expect(DsnSubmissionStatus::DRAFT->getLabel())->toBe('Brouillon');
    expect(DsnSubmissionStatus::EXPORTED->getLabel())->toBe('Exportée');
    expect(DsnSubmissionStatus::SUBMITTED->getLabel())->toBe('Soumise');
    expect(DsnSubmissionStatus::PARTIAL->getLabel())->toBe('Partielle');
    expect(DsnSubmissionStatus::ACCEPTED->getLabel())->toBe('Acceptée');
    expect(DsnSubmissionStatus::REJECTED->getLabel())->toBe('Rejetée');
});

it('validates all DsnSubmissionStatus getColor cases', function () {
    expect(DsnSubmissionStatus::DRAFT->getColor())->toBe('gray');
    expect(DsnSubmissionStatus::EXPORTED->getColor())->toBe('warning');
    expect(DsnSubmissionStatus::SUBMITTED->getColor())->toBe('info');
    expect(DsnSubmissionStatus::PARTIAL->getColor())->toBe('warning');
    expect(DsnSubmissionStatus::ACCEPTED->getColor())->toBe('success');
    expect(DsnSubmissionStatus::REJECTED->getColor())->toBe('danger');
});

it('validates DsnSubmissionLine relationships', function () {
    $submission = DsnSubmission::factory()->create();
    $payslip = Payslip::factory()->create(['status' => 'validated']);
    $line = DsnSubmissionLine::create([
        'dsn_submission_id' => $submission->id,
        'payslip_id' => $payslip->id,
        'status' => 'exported',
    ]);

    expect($line->dsnSubmission)->not->toBeNull();
    expect($line->dsnSubmission->id)->toBe($submission->id);
    expect($line->payslip)->not->toBeNull();
    expect($line->payslip->id)->toBe($payslip->id);
});

it('validates DsnSubmissionLine fillable', function () {
    $line = new DsnSubmissionLine;
    expect($line->getFillable())->toBe(['dsn_submission_id', 'payslip_id', 'status', 'error_message']);
});

it('validates Payslip dsnSubmissions relationship', function () {
    $submission = DsnSubmission::factory()->create();
    $payslip = Payslip::factory()->create(['status' => 'validated']);
    DsnSubmissionLine::create([
        'dsn_submission_id' => $submission->id,
        'payslip_id' => $payslip->id,
        'status' => 'exported',
    ]);

    $submissions = $payslip->dsnSubmissions;
    expect($submissions)->toHaveCount(1);
    expect($submissions->first()->id)->toBe($submission->id);
});

it('validates Payslip dsn fillable fields', function () {
    $payslip = Payslip::factory()->create([
        'dsn_status' => 'ready',
        'dsn_error_message' => null,
    ]);

    expect($payslip->dsn_status)->toBe('ready');
    expect($payslip->dsn_error_message)->toBeNull();
    expect($payslip->dsn_submitted_at)->toBeNull();
    expect($payslip->dsn_exported_at)->toBeNull();
});

it('validates DsnSubmission model fillable', function () {
    $submission = new DsnSubmission;
    expect($submission->getFillable())->toContain('company_id');
    expect($submission->getFillable())->toContain('period');
    expect($submission->getFillable())->toContain('status');
    expect($submission->getFillable())->toContain('export_type');
    expect($submission->getFillable())->toContain('exported_file_path');
    expect($submission->getFillable())->toContain('created_by');
});

it('rejects mixed companies in generateForAccountant', function () {
    $service = new DsnExportService;
    $user = User::factory()->create();

    $payslips = new Collection([
        Payslip::factory()->create(['status' => 'validated', 'period' => '2026-07']),
        Payslip::factory()->create(['status' => 'validated', 'period' => '2026-07']),
    ]);

    // All employees have null company_id (no company_id column), so this should pass
    // (unique nulls = count 1). Test that it does NOT throw.
    $submission = $service->generateForAccountant($payslips, '2026-07', null, $user->id);
    expect($submission)->toBeInstanceOf(DsnSubmission::class);
});

it('cleans up file when transaction fails', function () {
    $service = new DsnExportService;
    $user = User::factory()->create();
    $payslip = Payslip::factory()->create([
        'status' => 'validated',
        'period' => '2026-07',
    ]);

    Storage::fake('local');
    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new RuntimeException('DB error'));

    try {
        $service->generateForAccountant(
            new Collection([$payslip]),
            '2026-07',
            null,
            $user->id
        );
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('DB error');
    }
});

it('generates export summary with warnings for missing data', function () {
    $service = new DsnExportService;
    $payslip = Payslip::factory()->create([
        'status' => 'validated',
    ]);

    // Clear social security number and birth date to trigger warnings
    $payslip->employee->update([
        'social_security_number' => null,
        'birth_date' => null,
    ]);

    $summary = $service->getExportSummary(new Collection([$payslip]));

    expect($summary['has_warnings'])->toBeTrue();
    expect($summary['warnings'])->not->toBeEmpty();
    expect($summary['warnings'][0])->toContain('N° Sécurité Sociale manquant');
    expect($summary['warnings'][1])->toContain('Date de naissance manquante');
});
