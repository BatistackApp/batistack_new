<?php

use App\Models\Paie\SalaryPaymentRun;
use App\Models\User;
use App\Policies\Paie\SalaryPaymentRunPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->run = new SalaryPaymentRun;
    $this->policy = new SalaryPaymentRunPolicy;
});

function grantRun(string $permission): void
{
    test()->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
}

it('denies every ability to a user without permissions', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse()
        ->and($this->policy->view($this->user, $this->run))->toBeFalse()
        ->and($this->policy->create($this->user))->toBeFalse()
        ->and($this->policy->update($this->user, $this->run))->toBeFalse()
        ->and($this->policy->delete($this->user, $this->run))->toBeFalse()
        ->and($this->policy->deleteAny($this->user))->toBeFalse()
        ->and($this->policy->restore($this->user, $this->run))->toBeFalse()
        ->and($this->policy->forceDelete($this->user, $this->run))->toBeFalse()
        ->and($this->policy->forceDeleteAny($this->user))->toBeFalse()
        ->and($this->policy->restoreAny($this->user))->toBeFalse()
        ->and($this->policy->replicate($this->user, $this->run))->toBeFalse()
        ->and($this->policy->reorder($this->user))->toBeFalse();
});

it('authorizes the matching ability when the permission is granted', function () {
    grantRun('ViewAny:SalaryPaymentRun');
    expect($this->policy->viewAny($this->user))->toBeTrue();

    grantRun('View:SalaryPaymentRun');
    expect($this->policy->view($this->user, $this->run))->toBeTrue();

    grantRun('Create:SalaryPaymentRun');
    expect($this->policy->create($this->user))->toBeTrue();

    grantRun('Update:SalaryPaymentRun');
    expect($this->policy->update($this->user, $this->run))->toBeTrue();

    grantRun('Delete:SalaryPaymentRun');
    expect($this->policy->delete($this->user, $this->run))->toBeTrue();

    grantRun('DeleteAny:SalaryPaymentRun');
    expect($this->policy->deleteAny($this->user))->toBeTrue();

    grantRun('Restore:SalaryPaymentRun');
    expect($this->policy->restore($this->user, $this->run))->toBeTrue();

    grantRun('ForceDelete:SalaryPaymentRun');
    expect($this->policy->forceDelete($this->user, $this->run))->toBeTrue();

    grantRun('ForceDeleteAny:SalaryPaymentRun');
    expect($this->policy->forceDeleteAny($this->user))->toBeTrue();

    grantRun('RestoreAny:SalaryPaymentRun');
    expect($this->policy->restoreAny($this->user))->toBeTrue();

    grantRun('Replicate:SalaryPaymentRun');
    expect($this->policy->replicate($this->user, $this->run))->toBeTrue();

    grantRun('Reorder:SalaryPaymentRun');
    expect($this->policy->reorder($this->user))->toBeTrue();
});
