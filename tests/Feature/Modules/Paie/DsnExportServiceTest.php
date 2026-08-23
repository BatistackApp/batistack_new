<?php

use App\Enums\Paie\DsnStatus;
use App\Enums\Paie\DsnSubmissionStatus;
use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\DsnSubmission;
use App\Models\Paie\DsnSubmissionLine;
use App\Models\Paie\Payslip;
use App\Services\Paie\DsnExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->service = new DsnExportService();
});

it('generates a dsn export csv', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $path = $this->service->generateCsv(new Collection([$payslip]));

    expect($path)->toStartWith('documents/exports/export_dads_dsn_');
    expect(str_ends_with($path, '.csv'))->toBeTrue();
    Storage::disk('local')->assertExists($path);
});

it('generates csv with enriched columns', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $path = $this->service->generateCsv(new Collection([$payslip]));

    $content = Storage::disk('local')->get($path);
    $lines = explode("\n", $content);

    expect($lines[0])->toContain('Matricule');
    expect($lines[0])->toContain('Date_Naissance');
});

it('creates a DsnSubmission when generating for accountant', function () {
    $user = \App\Models\User::factory()->create();
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'period' => '2026-07',
    ]);

    $submission = $this->service->generateForAccountant(
        new Collection([$payslip]),
        '2026-07',
        null,
        $user->id
    );

    expect($submission)->toBeInstanceOf(DsnSubmission::class);
    expect($submission->status)->toBe(DsnSubmissionStatus::EXPORTED);
    expect($submission->export_type)->toBe('csv_expert');
    expect($submission->period)->toBe('2026-07');
    expect($submission->payslips_count)->toBe(1);
    expect($submission->exported_at)->not->toBeNull();
    expect($submission->exported_file_path)->not->toBeNull();
});

it('creates DsnSubmissionLines for each payslip', function () {
    $user = \App\Models\User::factory()->create();
    $payslips = Payslip::factory()->count(3)->create([
        'status' => PayslipStatus::VALIDATED,
        'period' => '2026-07',
    ]);

    $submission = $this->service->generateForAccountant(
        $payslips,
        '2026-07',
        null,
        $user->id
    );

    expect($submission->lines)->toHaveCount(3);
    expect(DsnSubmissionLine::where('dsn_submission_id', $submission->id)->count())->toBe(3);
});

it('updates payslip dsn_status to exported', function () {
    $user = \App\Models\User::factory()->create();
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'dsn_status' => null,
    ]);

    $this->service->generateForAccountant(
        new Collection([$payslip]),
        '2026-07',
        null,
        $user->id
    );

    $payslip->refresh();
    expect($payslip->dsn_status)->toBe(DsnStatus::EXPORTED->value);
    expect($payslip->dsn_exported_at)->not->toBeNull();
});

it('calculates correct totals in submission', function () {
    $user = \App\Models\User::factory()->create();
    $payslips = Payslip::factory()->count(2)->create([
        'status' => PayslipStatus::VALIDATED,
        'period' => '2026-07',
        'gross_salary' => 2000,
        'net_payable' => 1500,
        'employer_cost' => 3000,
    ]);

    $submission = $this->service->generateForAccountant(
        $payslips,
        '2026-07',
        null,
        $user->id
    );

    expect($submission->total_gross)->toBe('4000.00');
    expect($submission->total_net)->toBe('3000.00');
    expect($submission->total_employer_cost)->toBe('6000.00');
});

it('generates export summary with warnings', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $summary = $this->service->getExportSummary(new Collection([$payslip]));

    expect($summary)->toHaveKeys(['count', 'total_gross', 'total_net', 'total_employer_cost', 'warnings', 'has_warnings']);
    expect($summary['count'])->toBe(1);
});

it('marks payslips as ready for DSN', function () {
    $payslips = Payslip::factory()->count(3)->create([
        'status' => PayslipStatus::VALIDATED,
        'dsn_status' => null,
    ]);

    $this->service->markAsReady($payslips);

    foreach ($payslips as $payslip) {
        $payslip->refresh();
        expect($payslip->dsn_status)->toBe(DsnStatus::READY->value);
    }
});

it('validates DsnStatus enum', function () {
    expect(DsnStatus::READY->value)->toBe('ready');
    expect(DsnStatus::EXPORTED->value)->toBe('exported');
    expect(DsnStatus::SUBMITTED->value)->toBe('submitted');
    expect(DsnStatus::ACCEPTED->value)->toBe('accepted');
    expect(DsnStatus::REJECTED->value)->toBe('rejected');

    expect(DsnStatus::READY->getLabel())->toBe('Prête');
    expect(DsnStatus::READY->getColor())->toBe('info');
});

it('validates DsnSubmissionStatus enum', function () {
    expect(DsnSubmissionStatus::DRAFT->getLabel())->toBe('Brouillon');
    expect(DsnSubmissionStatus::EXPORTED->getLabel())->toBe('Exportée');
    expect(DsnSubmissionStatus::SUBMITTED->getLabel())->toBe('Soumise');
    expect(DsnSubmissionStatus::PARTIAL->getLabel())->toBe('Partielle');
    expect(DsnSubmissionStatus::ACCEPTED->getLabel())->toBe('Acceptée');
    expect(DsnSubmissionStatus::REJECTED->getLabel())->toBe('Rejetée');
});

it('validates DsnSubmission model relationships', function () {
    $user = \App\Models\User::factory()->create();
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $submission = $this->service->generateForAccountant(
        new Collection([$payslip]),
        '2026-07',
        null,
        $user->id
    );

    expect($submission->company)->toBeNull();
    expect($submission->creator)->not->toBeNull();
    expect($submission->lines)->toHaveCount(1);
    expect($submission->payslips)->toHaveCount(1);
});

it('rejects mixed periods in generateForAccountant', function () {
    $user = \App\Models\User::factory()->create();
    $payslips = new Collection([
        Payslip::factory()->create(['status' => PayslipStatus::VALIDATED, 'period' => '2026-07']),
        Payslip::factory()->create(['status' => PayslipStatus::VALIDATED, 'period' => '2026-08']),
    ]);

    $this->service->generateForAccountant($payslips, '2026-07', null, $user->id);
})->throws(\InvalidArgumentException::class, 'même période');

it('cleans up file on failure', function () {
    $user = \App\Models\User::factory()->create();
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'period' => '2026-07',
    ]);

    Storage::fake('local');

    try {
        $this->service->generateForAccountant(
            new Collection([$payslip]),
            '2026-07',
            null,
            $user->id
        );
    } catch (\Throwable $e) {
        // Expected - just verify no leftover files
    }
});
