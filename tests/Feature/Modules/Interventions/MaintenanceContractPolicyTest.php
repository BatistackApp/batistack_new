<?php

use App\Models\Interventions\MaintenanceContract;
use App\Models\User;
use App\Policies\Interventions\MaintenanceContractPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->contract = MaintenanceContract::factory()->create();
    $this->policy = new MaintenanceContractPolicy;
});

function grant(string $permission): void
{
    test()->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
}

it('denies every ability to a user without permissions', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse()
        ->and($this->policy->view($this->user, $this->contract))->toBeFalse()
        ->and($this->policy->create($this->user))->toBeFalse()
        ->and($this->policy->update($this->user, $this->contract))->toBeFalse()
        ->and($this->policy->delete($this->user, $this->contract))->toBeFalse()
        ->and($this->policy->deleteAny($this->user))->toBeFalse()
        ->and($this->policy->restore($this->user, $this->contract))->toBeFalse()
        ->and($this->policy->forceDelete($this->user, $this->contract))->toBeFalse()
        ->and($this->policy->forceDeleteAny($this->user))->toBeFalse()
        ->and($this->policy->restoreAny($this->user))->toBeFalse()
        ->and($this->policy->replicate($this->user, $this->contract))->toBeFalse();
});

it('authorizes the matching ability when the permission is granted', function () {
    grant('ViewAny:MaintenanceContract');
    expect($this->policy->viewAny($this->user))->toBeTrue();

    grant('View:MaintenanceContract');
    expect($this->policy->view($this->user, $this->contract))->toBeTrue();

    grant('Create:MaintenanceContract');
    expect($this->policy->create($this->user))->toBeTrue();

    grant('Update:MaintenanceContract');
    expect($this->policy->update($this->user, $this->contract))->toBeTrue();

    grant('Delete:MaintenanceContract');
    expect($this->policy->delete($this->user, $this->contract))->toBeTrue();

    grant('DeleteAny:MaintenanceContract');
    expect($this->policy->deleteAny($this->user))->toBeTrue();

    grant('Restore:MaintenanceContract');
    expect($this->policy->restore($this->user, $this->contract))->toBeTrue();

    grant('ForceDelete:MaintenanceContract');
    expect($this->policy->forceDelete($this->user, $this->contract))->toBeTrue();

    grant('ForceDeleteAny:MaintenanceContract');
    expect($this->policy->forceDeleteAny($this->user))->toBeTrue();

    grant('RestoreAny:MaintenanceContract');
    expect($this->policy->restoreAny($this->user))->toBeTrue();

    grant('Replicate:MaintenanceContract');
    expect($this->policy->replicate($this->user, $this->contract))->toBeTrue();
});
