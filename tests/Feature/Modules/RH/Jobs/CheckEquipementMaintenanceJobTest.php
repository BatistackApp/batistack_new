<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Jobs\RH\CheckEquipementMaintenanceJob;
use App\Models\RH\Equipement;
use App\Models\User;
use App\Notifications\RH\EquipementMaintenanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('CheckEquipementMaintenanceJob', function () {
    it('notifies admins of expired equipments and missing maintenance', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        // Expired
        $expiredEquipement = Equipement::factory()->create([
            'expires_at' => now()->subDays(5),
            'last_check_at' => now(), // Checked but expired
        ]);

        // Missing maintenance (null)
        $noCheckEquipement = Equipement::factory()->create([
            'expires_at' => now()->addDays(30),
            'last_check_at' => null,
        ]);

        // Missing maintenance (old)
        $oldCheckEquipement = Equipement::factory()->create([
            'expires_at' => now()->addDays(30),
            'last_check_at' => now()->subDays(370),
        ]);

        // Valid
        $validEquipement = Equipement::factory()->create([
            'expires_at' => now()->addDays(30),
            'last_check_at' => now()->subDays(10),
        ]);

        Log::shouldReceive('warning')
            ->with('Expired equipement detected', ['equipement_id' => $expiredEquipement->id, 'employee_id' => $expiredEquipement->employee_id])
            ->once();

        Log::shouldReceive('warning')
            ->with('Equipement maintenance overdue', ['equipement_id' => $noCheckEquipement->id, 'employee_id' => $noCheckEquipement->employee_id])
            ->once();

        Log::shouldReceive('warning')
            ->with('Equipement maintenance overdue', ['equipement_id' => $oldCheckEquipement->id, 'employee_id' => $oldCheckEquipement->employee_id])
            ->once();

        Log::shouldReceive('info')
            ->with('CheckEquipementMaintenanceJob completed', [
                'expired_count' => 1,
                'maintenance_count' => 2,
            ])
            ->once();

        $job = new CheckEquipementMaintenanceJob;
        $job->handle();

        Notification::assertSentTo(
            [$admin],
            EquipementMaintenanceNotification::class,
            function ($notification) use ($expiredEquipement) {
                return (fn () => $this->equipement->id)->call($notification) === $expiredEquipement->id
                    && (fn () => $this->type)->call($notification) === 'expired';
            }
        );

        Notification::assertSentTo(
            [$admin],
            EquipementMaintenanceNotification::class,
            function ($notification) use ($noCheckEquipement) {
                return (fn () => $this->equipement->id)->call($notification) === $noCheckEquipement->id
                    && (fn () => $this->type)->call($notification) === 'maintenance';
            }
        );

        Notification::assertSentTo(
            [$admin],
            EquipementMaintenanceNotification::class,
            function ($notification) use ($oldCheckEquipement) {
                return (fn () => $this->equipement->id)->call($notification) === $oldCheckEquipement->id
                    && (fn () => $this->type)->call($notification) === 'maintenance';
            }
        );

        Notification::assertNotSentTo(
            [$admin],
            EquipementMaintenanceNotification::class,
            function ($notification) use ($validEquipement) {
                return (fn () => $this->equipement->id)->call($notification) === $validEquipement->id;
            }
        );
    });
});
