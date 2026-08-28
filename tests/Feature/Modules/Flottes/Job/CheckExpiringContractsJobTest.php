<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Enums\Flottes\VehicleType;
use App\Jobs\Flottes\CheckExpiringContractsJob;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\User;
use App\Notifications\Flottes\ContractExpiringNotification;
use App\Notifications\Flottes\VulPollutionControlAlertNotification;
use App\Services\Flottes\VehicleAlertService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

describe('CheckExpiringContractsJob', function () {
    it('notifies admins of expiring contracts, expired contracts, and VUL pollution controls', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $expiringContract = VehicleContract::factory()->create(['annual_cost_ht' => 1000]);
        $expiredContract = VehicleContract::factory()->create(['annual_cost_ht' => 1000]);

        $serviceMock = Mockery::mock(VehicleAlertService::class);
        $serviceMock->shouldReceive('getExpiringContracts')
            ->once()
            ->with(30)
            ->andReturn(Collection::make([$expiringContract]));

        $serviceMock->shouldReceive('getExpiredContracts')
            ->once()
            ->andReturn(Collection::make([$expiredContract]));

        $vulVehicle = Vehicle::factory()->create([
            'type' => VehicleType::UTILITY,
            'pollution_control_due_at' => now()->addDays(15),
        ]);

        $nonVulVehicle = Vehicle::factory()->create([
            'type' => VehicleType::PASSENGER,
            'pollution_control_due_at' => now()->addDays(15),
        ]);

        Log::shouldReceive('info')
            ->with("Alerte VUL : {$vulVehicle->reference} contrôle pollution dans 30 jours")
            ->once();

        Log::shouldReceive('warning')
            ->with("Contrat EXPIRÉ : {$expiredContract->vehicle->reference} - {$expiredContract->type}")
            ->once();

        Log::shouldReceive('info')
            ->with('Scan conformité : 3 alertes')
            ->once();

        $job = new CheckExpiringContractsJob;
        $job->handle($serviceMock);

        Notification::assertSentTo(
            [$admin],
            ContractExpiringNotification::class,
            function ($notification) use ($expiringContract) {
                return (fn () => $this->contract->id)->call($notification) === $expiringContract->id;
            }
        );

        Notification::assertSentTo(
            [$admin],
            ContractExpiringNotification::class,
            function ($notification) use ($expiredContract) {
                return (fn () => $this->contract->id)->call($notification) === $expiredContract->id;
            }
        );

        Notification::assertSentTo(
            [$admin],
            VulPollutionControlAlertNotification::class,
            function ($notification) use ($vulVehicle) {
                return (fn () => $this->vehicle->id)->call($notification) === $vulVehicle->id;
            }
        );

        Notification::assertNotSentTo([$user], ContractExpiringNotification::class);
        Notification::assertNotSentTo([$user], VulPollutionControlAlertNotification::class);
    });
});
