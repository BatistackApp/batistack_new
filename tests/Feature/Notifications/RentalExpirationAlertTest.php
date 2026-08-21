<?php

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Tiers\ThirdParty;
use App\Jobs\Locations\RentalExpirationAlertJob;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

it('envoie une notification RentalExpirationAlert au manager pour les contrats expirant dans 3 jours', function () {
    Notification::fake();
    
    $managerUser = User::factory()->create(['email' => 'manager-exp-' . uniqid() . '@test.com', 'name' => 'Manager Exp']);
    $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
    $chantier = Chantier::factory()->create(['manager_id' => $manager->id]);
    $supplier = ThirdParty::factory()->create();
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'chantier_id' => $chantier->id,
        'reference' => 'LOC-EXPIRE-STUB',
        'status' => RentalStatus::ACTIVE,
        'end_date' => Carbon::today()->addDays(3),
    ]);
    
    $job = new RentalExpirationAlertJob();
    $job->handle();
    
    Notification::assertSentTo(
        $managerUser,
        \App\Notifications\RentalExpirationAlert::class,
        function ($notification) use ($contract) {
            expect($notification->contract->id)->toBe($contract->id);
            expect($notification->daysUntilExpiration)->toBe(3);
            return true;
        }
    );
});
