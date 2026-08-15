<?php

use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\User;
use App\Policies\Immobilisation\AssetMaintenanceTicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();
    $this->policy = new AssetMaintenanceTicketPolicy;
});

function grantTicket(string $permission): void
{
    test()->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
}

it('denies every ability to a user without permissions', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse()
        ->and($this->policy->view($this->user, $this->ticket))->toBeFalse()
        ->and($this->policy->create($this->user))->toBeFalse()
        ->and($this->policy->update($this->user, $this->ticket))->toBeFalse()
        ->and($this->policy->delete($this->user, $this->ticket))->toBeFalse()
        ->and($this->policy->deleteAny($this->user))->toBeFalse()
        ->and($this->policy->restore($this->user, $this->ticket))->toBeFalse()
        ->and($this->policy->forceDelete($this->user, $this->ticket))->toBeFalse();
});

it('authorizes the matching ability when the permission is granted', function () {
    grantTicket('ViewAny:AssetMaintenanceTicket');
    expect($this->policy->viewAny($this->user))->toBeTrue();

    grantTicket('View:AssetMaintenanceTicket');
    expect($this->policy->view($this->user, $this->ticket))->toBeTrue();

    grantTicket('Create:AssetMaintenanceTicket');
    expect($this->policy->create($this->user))->toBeTrue();

    grantTicket('Update:AssetMaintenanceTicket');
    expect($this->policy->update($this->user, $this->ticket))->toBeTrue();

    grantTicket('Delete:AssetMaintenanceTicket');
    expect($this->policy->delete($this->user, $this->ticket))->toBeTrue();

    grantTicket('DeleteAny:AssetMaintenanceTicket');
    expect($this->policy->deleteAny($this->user))->toBeTrue();

    grantTicket('Restore:AssetMaintenanceTicket');
    expect($this->policy->restore($this->user, $this->ticket))->toBeTrue();

    grantTicket('ForceDelete:AssetMaintenanceTicket');
    expect($this->policy->forceDelete($this->user, $this->ticket))->toBeTrue();
});
