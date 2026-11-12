<?php

use App\Filament\Flottes\Resources\Vehicles\Pages\ListVehicles;
use App\Models\Core\Company;
use App\Models\Core\GeneratedDocument;
use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Services\Flottes\FleetCostService;
use App\Services\Flottes\FleetDocumentService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Browsershot\Browsershot;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function fakeFleetFicheService(string $behaviour = 'ok'): FleetDocumentService
{
    return new class($behaviour) extends FleetDocumentService
    {
        public function __construct(private string $behaviour)
        {
            parent::__construct(app(FleetCostService::class));
        }

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
    $this->admin->givePermissionTo(Permission::findOrCreate('ViewAny:Vehicle', 'web'));

    Filament::setCurrentPanel(Filament::getPanel('flottes'));
    $this->actingAs($this->admin);

    $this->vehicle = Vehicle::factory()->create();
});

it('generates, indexes and serves the vehicle fiche pdf', function () {
    $this->app->bind(FleetDocumentService::class, fn () => fakeFleetFicheService('ok'));

    Livewire::test(ListVehicles::class)
        ->callTableAction('printFiche', $this->vehicle);

    $path = "documents/flotte/vehicles/fiche_vehicule_{$this->vehicle->reference}.pdf";

    expect(Storage::disk('public')->exists($path))->toBeTrue()
        ->and(GeneratedDocument::query()
            ->where('file_path', $path)
            ->where('file_disk', 'public')
            ->exists())->toBeTrue();
});

it('notifies the user instead of failing when fiche generation breaks', function () {
    $this->app->bind(FleetDocumentService::class, fn () => fakeFleetFicheService('throw'));

    Livewire::test(ListVehicles::class)
        ->callTableAction('printFiche', $this->vehicle)
        ->assertNotified('Échec de la génération de la fiche');

    expect(GeneratedDocument::query()
        ->where('file_path', 'like', 'documents/flotte/vehicles/%')
        ->exists())->toBeFalse();
});
