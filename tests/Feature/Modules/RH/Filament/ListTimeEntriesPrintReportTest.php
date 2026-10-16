<?php

use App\Enums\RH\TimeEntryStatus;
use App\Filament\RH\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Models\Core\Company;
use App\Models\Core\GeneratedDocument;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Services\RH\RHDocumentService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Browsershot\Browsershot;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function fakeRhTimesheetService(string $behaviour = 'ok'): RHDocumentService
{
    return new class($behaviour) extends RHDocumentService
    {
        public function __construct(private string $behaviour) {}

        protected function makeBrowsershot(string $html): Browsershot
        {
            $mock = Mockery::mock(Browsershot::class);
            $mock->shouldReceive('setNodeBinary')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('setNpmBinary')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('showBackground')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('waitUntilNetworkIdle')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('noSandbox')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('format')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('margins')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('landscape')->withAnyArgs()->andReturnSelf();
            $mock->shouldReceive('setChromePath')->withAnyArgs()->andReturnSelf();

            if ($this->behaviour === 'throw') {
                $process = new Process(['php', '-r', 'exit(1);']);
                $process->run();
                $mock->shouldReceive('pdf')->andThrow(new ProcessFailedException($process));
            } else {
                $mock->shouldReceive('pdf')->andReturn('%PDF-1.7 mock content');
            }

            return $mock;
        }
    };
}

beforeEach(function () {
    Storage::fake('public');
    Company::factory()->create();

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->admin->givePermissionTo(Permission::findOrCreate('ViewAny:TimeEntry', 'web'));

    Filament::setCurrentPanel(Filament::getPanel('rh'));
    $this->actingAs($this->admin);

    $this->employee = Employee::factory()->create();
});

it('generates, indexes and serves the monthly timesheet pdf', function () {
    $month = now()->month;
    $year = now()->year;

    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => now()->startOfMonth()->addDays(4),
        'hours' => 8,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $this->app->bind(RHDocumentService::class, fn () => fakeRhTimesheetService('ok'));

    Livewire::test(ListTimeEntries::class)
        ->callAction('printMonthlyReport', data: [
            'employee_id' => $this->employee->id,
            'month' => $month,
            'year' => $year,
        ]);

    $path = "documents/rh/timesheets/releve_heures_{$this->employee->id}_{$year}_{$month}.pdf";

    expect(Storage::disk('public')->exists($path))->toBeTrue()
        ->and(GeneratedDocument::query()
            ->where('file_path', $path)
            ->where('file_disk', 'public')
            ->exists())->toBeTrue();
});

it('notifies the user instead of failing when pdf generation breaks', function () {
    $this->app->bind(RHDocumentService::class, fn () => fakeRhTimesheetService('throw'));

    Livewire::test(ListTimeEntries::class)
        ->callAction('printMonthlyReport', data: [
            'employee_id' => $this->employee->id,
            'month' => now()->month,
            'year' => now()->year,
        ])
        ->assertNotified('Échec de la génération du relevé');

    expect(GeneratedDocument::query()
        ->where('file_path', 'like', 'documents/rh/timesheets/%')
        ->exists())->toBeFalse();
});
