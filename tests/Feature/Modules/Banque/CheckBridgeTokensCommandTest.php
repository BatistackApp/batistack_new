<?php

use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Models\User;
use App\Services\Banque\BridgeApiService;
use Filament\Notifications\Notification;

it('checks bridge tokens and sends notifications when expiring', function () {
    $company = Company::factory()->create();
    BankAccount::factory()->create(['company_id' => $company->id, 'bridge_account_id' => 'bridge-123']);

    $admin = User::factory()->create(['is_admin' => true]);

    $mockService = Mockery::mock(BridgeApiService::class);
    $mockService->shouldReceive('checkItemsExpiration')
        ->with($company->id)
        ->once()
        ->andReturn([
            [
                'item_id' => 123,
                'bank_name' => 456,
                'expires_at' => now()->addDays(3)->toIso8601String(),
                'days_remaining' => 3,
            ],
        ]);

    $this->app->instance(BridgeApiService::class, $mockService);

    $this->artisan('app:check-bridge-tokens')
        ->expectsOutput("Vérification des connexions pour l'entreprise ID: {$company->id}")
        ->expectsOutput("Trouvé 1 connexion(s) expirant bientôt pour l'entreprise {$company->id}.")
        ->assertSuccessful();

    // Verify that a notification was sent to the database
    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $admin->id,
    ]);
});
