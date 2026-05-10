<?php

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\ContactService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

// Initialisation avant chaque test
beforeEach(function () {
    Queue::fake(); // Empêche les jobs d'être réellement dispatchés
    // Mock des services externes si ContactService en avait besoin directement.
    // Pour ce service, les dépendances sont injectées directement via le constructeur
    // s'il y en avait, ou via les facades.
    $this->contactService = new ContactService;
});

// Test pour la méthode syncContact
it('synchronizes a contact for a third party (create)', function () {
    $thirdParty = ThirdParty::factory()->create();
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '0123456789',
        'position' => 'Manager',
        'is_primary' => true,
    ];

    $contact = $this->contactService->syncContact($thirdParty, $data);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->third_party_id)->toEqual($thirdParty->id)
        ->and($contact->email)->toEqual('john.doe@example.com')
        ->and($contact->is_primary)->toBeTrue()
        ->and($contact->is_active)->toBeTrue();

    $this->assertDatabaseHas('contacts', [
        'third_party_id' => $thirdParty->id,
        'email' => 'john.doe@example.com',
        'is_primary' => true,
        'is_active' => true,
    ]);
});

it('synchronizes a contact for a third party (update existing and set as primary)', function () {
    $thirdParty = ThirdParty::factory()->create();
    $existingContact = Contact::factory()->for($thirdParty)->create([
        'email' => 'jane.doe@example.com',
        'first_name' => 'Jane',
        'is_primary' => false,
    ]);
    $otherPrimaryContact = Contact::factory()->for($thirdParty)->create([
        'email' => 'primary.guy@example.com',
        'is_primary' => true,
    ]);

    $data = [
        'first_name' => 'Janet',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'phone' => '0987654321',
        'position' => 'Senior Manager',
        'is_primary' => true,
    ];

    $contact = $this->contactService->syncContact($thirdParty, $data);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->id)->toEqual($existingContact->id)
        ->and($contact->first_name)->toEqual('Janet')
        ->and($contact->is_primary)->toBeTrue();
    // Vérifie que c'est le même contact

    // L'ancien contact principal devrait être mis à jour pour ne plus être principal
    $this->assertDatabaseHas('contacts', [
        'id' => $otherPrimaryContact->id,
        'is_primary' => false,
    ]);
    $this->assertDatabaseHas('contacts', [
        'id' => $existingContact->id,
        'is_primary' => true,
    ]);
});

it('throws TiersModuleException if syncContact fails', function () {
    $thirdParty = ThirdParty::factory()->create();

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '0123456789',
        'position' => 'Manager',
    ];

    // 1. Créez un mock de l'objet de relation HasMany
    $hasManyMock = Mockery::mock(HasMany::class);

    // 2. Faites en sorte que la méthode updateOrCreate sur ce mock lève une exception
    $hasManyMock->shouldReceive('updateOrCreate')
        ->once()
        ->with(
            ['email' => $data['email']],
            array_merge($data, ['is_active' => true])
        )
        ->andThrow(new Exception('Simulated DB error in updateOrCreate'));

    // 3. Créez un mock partiel du ThirdParty pour intercepter l'appel à contacts()
    $mockThirdParty = Mockery::mock($thirdParty)->makePartial();
    $mockThirdParty->shouldReceive('contacts')
        ->once()
        ->andReturn($hasManyMock); // Quand contacts() est appelé, il retourne notre mock de relation

    Log::shouldReceive('critical')->once();

    $this->expectException(TiersModuleException::class);
    $this->expectExceptionMessage('Création du contact impossible');

    // Appelez la méthode du service avec le mock du ThirdParty
    $this->contactService->syncContact($mockThirdParty, $data);
});

// Test pour la méthode setAsPrimary
it('sets a contact as primary and unsets others', function () {
    $thirdParty = ThirdParty::factory()->create();
    $contact1 = Contact::factory()->for($thirdParty)->create(['is_primary' => true]);
    $contact2 = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);
    $contact3 = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);

    $this->contactService->setAsPrimary($contact2);

    // Recharger les contacts pour obtenir les états mis à jour
    $contact1->refresh();
    $contact2->refresh();
    $contact3->refresh();

    expect($contact1->is_primary)->toBeFalse()
        ->and($contact2->is_primary)->toBeTrue()
        ->and($contact3->is_primary)->toBeFalse();

    $this->assertDatabaseHas('contacts', ['id' => $contact1->id, 'is_primary' => false]);
    $this->assertDatabaseHas('contacts', ['id' => $contact2->id, 'is_primary' => true]);
    $this->assertDatabaseHas('contacts', ['id' => $contact3->id, 'is_primary' => false]);
});

it('throws TiersModuleException if setAsPrimary fails', function () {
    $contact = Contact::factory()->create();

    // Pour simuler une erreur, nous allons mocker la méthode `update` du contact
    // de sorte qu'elle lance une exception lors de l'appel.
    $mockContact = Mockery::mock($contact);
    $mockContact->shouldReceive('update')
        ->with(['is_primary' => true])
        ->andThrow(new Exception('Simulated DB update error'));
    $mockContact->shouldReceive('getAttribute')->andReturnUsing(fn ($key) => $contact->getAttribute($key));

    Log::shouldReceive('critical')->once();

    $this->expectException(TiersModuleException::class);
    $this->expectExceptionMessage('Mise à jour du contact principal impossible');

    // Il faut passer le mock au lieu de l'original
    $this->contactService->setAsPrimary($mockContact);
});

// Test pour la méthode archive
it('archives a contact correctly', function () {
    $contact = Contact::factory()->create(['is_active' => true, 'is_primary' => true]);
    $reason = 'Left the company';

    $this->contactService->archive($contact, $reason);

    $contact->refresh();

    expect($contact->is_active)->toBeFalse()
        ->and($contact->is_primary)->toBeFalse()
        ->and($contact->metadata)->toHaveKey('archived_at')
        ->and($contact->metadata['archive_reason'])->toEqual($reason);

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'is_active' => false,
        'is_primary' => false,
    ]);
});

// Test pour la méthode reassignResponsibilities
it('reassigns responsibilities from one contact to another', function () {
    $thirdParty = ThirdParty::factory()->create();
    $contactFrom = Contact::factory()->for($thirdParty)->create(['is_primary' => true]);
    $contactTo = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);

    $this->contactService->reassignResponsibilities($contactFrom, $contactTo);

    $contactFrom->refresh();
    $contactTo->refresh();

    // Vérifie que le nouveau contact est principal
    expect($contactTo->is_primary)->toBeTrue()
        ->and($contactFrom->is_active)->toBeFalse()
        ->and($contactFrom->is_primary)->toBeFalse()
        ->and($contactFrom->metadata)->toHaveKey('archived_at')
        ->and($contactFrom->metadata['archive_reason'])->toContain($contactTo->full_name);
    // Vérifie que l'ancien contact est archivé

    $this->assertDatabaseHas('contacts', [
        'id' => $contactTo->id,
        'is_primary' => true,
    ]);
    $this->assertDatabaseHas('contacts', [
        'id' => $contactFrom->id,
        'is_active' => false,
        'is_primary' => false,
    ]);
});

it('reassigns responsibilities from a non-primary contact', function () {
    $thirdParty = ThirdParty::factory()->create();
    $contactFrom = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);
    $contactTo = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);
    $originalPrimary = Contact::factory()->for($thirdParty)->create(['is_primary' => true]);

    $this->contactService->reassignResponsibilities($contactFrom, $contactTo);

    $contactFrom->refresh();
    $contactTo->refresh();
    $originalPrimary->refresh();

    // L'ancien contact principal doit rester principal
    expect($originalPrimary->is_primary)->toBeTrue()
        ->and($contactTo->is_primary)->toBeFalse()
        ->and($contactFrom->is_active)->toBeFalse()
        ->and($contactFrom->is_primary)->toBeFalse();
    // Le nouveau contact ne doit pas devenir principal (l'ancien `from` n'était pas principal)
    // L'ancien contact est archivé
});

it('throws TiersModuleException if reassignResponsibilities fails', function () {
    $thirdParty = ThirdParty::factory()->create();
    $contactFrom = Contact::factory()->for($thirdParty)->create(['is_primary' => true]);
    $contactTo = Contact::factory()->for($thirdParty)->create(['is_primary' => false]);

    // Simuler un échec dans la méthode `setAsPrimary` ou `archive` appelée en interne.
    // Pour simplifier, nous allons mocker `archive` de sorte qu'elle lance une exception.
    $mockContactService = Mockery::mock(ContactService::class)->makePartial();
    $mockContactService->shouldAllowMockingProtectedMethods(); // Permet de mocker les méthodes protégées/privées si besoin
    $mockContactService->shouldReceive('archive')
        ->once()
        ->with($contactFrom, Mockery::any())
        ->andThrow(new Exception('Simulated archive error'));
    $mockContactService->shouldReceive('setAsPrimary')->once()->with($contactTo)->andReturn(null); // setAsPrimary réussit

    Log::shouldReceive('critical')->once();

    $this->expectException(TiersModuleException::class);
    $this->expectExceptionMessage('Transfert de responsabilités impossible');

    // On utilise le mock du service
    $mockContactService->reassignResponsibilities($contactFrom, $contactTo);
});
