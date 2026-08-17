<?php

use App\Models\Interventions\InterventionReportTemplate;
use App\Models\User;
use App\Policies\Interventions\InterventionReportTemplatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->template = InterventionReportTemplate::factory()->create();
    $this->policy = new InterventionReportTemplatePolicy;
});

function grantReportTemplate(string $permission): void
{
    test()->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
}

it('denies every ability to a user without permissions', function () {
    expect($this->policy->viewAny($this->user))->toBeFalse()
        ->and($this->policy->view($this->user, $this->template))->toBeFalse()
        ->and($this->policy->create($this->user))->toBeFalse()
        ->and($this->policy->update($this->user, $this->template))->toBeFalse()
        ->and($this->policy->delete($this->user, $this->template))->toBeFalse()
        ->and($this->policy->deleteAny($this->user))->toBeFalse()
        ->and($this->policy->restore($this->user, $this->template))->toBeFalse()
        ->and($this->policy->forceDelete($this->user, $this->template))->toBeFalse()
        ->and($this->policy->forceDeleteAny($this->user))->toBeFalse()
        ->and($this->policy->restoreAny($this->user))->toBeFalse()
        ->and($this->policy->replicate($this->user, $this->template))->toBeFalse()
        ->and($this->policy->reorder($this->user))->toBeFalse();
});

it('authorizes the matching ability when the permission is granted', function () {
    grantReportTemplate('ViewAny:InterventionReportTemplate');
    expect($this->policy->viewAny($this->user))->toBeTrue();

    grantReportTemplate('View:InterventionReportTemplate');
    expect($this->policy->view($this->user, $this->template))->toBeTrue();

    grantReportTemplate('Create:InterventionReportTemplate');
    expect($this->policy->create($this->user))->toBeTrue();

    grantReportTemplate('Update:InterventionReportTemplate');
    expect($this->policy->update($this->user, $this->template))->toBeTrue();

    grantReportTemplate('Delete:InterventionReportTemplate');
    expect($this->policy->delete($this->user, $this->template))->toBeTrue();

    grantReportTemplate('DeleteAny:InterventionReportTemplate');
    expect($this->policy->deleteAny($this->user))->toBeTrue();

    grantReportTemplate('Restore:InterventionReportTemplate');
    expect($this->policy->restore($this->user, $this->template))->toBeTrue();

    grantReportTemplate('ForceDelete:InterventionReportTemplate');
    expect($this->policy->forceDelete($this->user, $this->template))->toBeTrue();

    grantReportTemplate('ForceDeleteAny:InterventionReportTemplate');
    expect($this->policy->forceDeleteAny($this->user))->toBeTrue();

    grantReportTemplate('RestoreAny:InterventionReportTemplate');
    expect($this->policy->restoreAny($this->user))->toBeTrue();

    grantReportTemplate('Replicate:InterventionReportTemplate');
    expect($this->policy->replicate($this->user, $this->template))->toBeTrue();

    grantReportTemplate('Reorder:InterventionReportTemplate');
    expect($this->policy->reorder($this->user))->toBeTrue();
});
