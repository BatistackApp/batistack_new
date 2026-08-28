<?php

use App\Exceptions\Tiers\TiersModuleException;
use App\Jobs\Tiers\GeocodeAddressJob;
use App\Models\Tiers\Address;
use App\Models\User;
use App\Services\Core\GoogleMapsService;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

test('it geocodes address successfully', function () {
    $address = Address::factory()->create([
        'street' => '10 rue de la Paix',
        'zip_code' => '75002',
        'city' => 'Paris',
        'latitude' => null,
        'longitude' => null,
    ]);

    // Mock du service
    $googleMapsMock = Mockery::mock(GoogleMapsService::class);
    $googleMapsMock->shouldReceive('geocodeAddress')
        ->once()
        ->with('10 rue de la Paix, 75002 Paris')
        ->andReturn(['lat' => 48.867, 'lng' => 2.333]);

    $job = new GeocodeAddressJob($address);
    $job->handle($googleMapsMock);

    $address->refresh();
    expect($address->latitude)->toBe(48.867)
        ->and($address->longitude)->toBe(2.333);
});

test('it sends filament notification when geocoding fails', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create();

    $googleMapsMock = Mockery::mock(GoogleMapsService::class);
    $googleMapsMock->shouldReceive('geocodeAddress')->andReturn(null);

    $job = new GeocodeAddressJob($address, $user);

    // Nettoyer les notifications potentiellement générées par les Observers lors des factory()
    DatabaseNotification::query()->delete();

    $job->handle($googleMapsMock);

    // 1. Vérifiez que la notification existe bien pour cet utilisateur
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'notifiable_type' => get_class($user),
    ]);

    // 2. Récupérez la notification et testez les propriétés spécifiques via l'objet décodé
    $notification = DatabaseNotification::where('notifiable_id', $user->id)->first();

    expect($notification->data)
        ->toHaveKey('title', 'Géocodage Impossible')
        ->toHaveKey('body', "L'adresse du tiers n'a pas pu être localisée précisément sur la carte.")
        ->toHaveKey('icon', 'phosphor-x-circle'); // Vérification optionnelle mais utile
});

test('it logs and throws exception on api error', function () {
    Log::shouldReceive('error')->once();

    $address = Address::factory()->create();

    $googleMapsMock = Mockery::mock(GoogleMapsService::class);
    $googleMapsMock->shouldReceive('geocodeAddress')
        ->andThrow(new Exception('API connection error'));

    $job = new GeocodeAddressJob($address);

    // On s'attend à ce que le job relance une TiersModuleException
    expect(fn () => $job->handle($googleMapsMock))->toThrow(TiersModuleException::class);
});
