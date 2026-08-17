<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Filament\Technicien\Pages\FillInterventionReportPage;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionReportTemplate;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);

    Filament::setCurrentPanel(Filament::getPanel('technicien'));
    $this->actingAs($this->user);
});

function fullSchema(): array
{
    return [
        ['type' => 'text_input', 'data' => ['name' => 'constat', 'label' => 'Constat', 'required' => true]],
        ['type' => 'number', 'data' => ['name' => 'pieces', 'label' => 'Nb pièces', 'required' => false]],
        ['type' => 'select', 'data' => ['name' => 'etat', 'label' => 'État', 'required' => true, 'options' => "OK\nKO"]],
        ['type' => 'checkbox', 'data' => ['name' => 'conforme', 'label' => 'Conforme', 'required' => true]],
        ['type' => 'textarea', 'data' => ['name' => 'commentaire', 'label' => 'Commentaire', 'required' => false]],
        ['type' => 'date', 'data' => ['name' => 'date_intervention', 'label' => 'Date', 'required' => false]],
        ['type' => 'file_upload', 'data' => ['name' => 'piece_jointe', 'label' => 'Photo / Fichier', 'required' => false]],
    ];
}

function makeReportTemplateContext(): Intervention
{
    InterventionReportTemplate::create([
        'name' => 'Rapport SAV',
        'intervention_type' => InterventionType::REGIE,
        'is_active' => true,
        'schema' => fullSchema(),
    ]);

    $intervention = Intervention::factory()->create([
        'company_id' => test()->company->id,
        'third_party_id' => test()->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::EN_COURS,
    ]);

    $intervention->workers()->create(['employee_id' => test()->employee->id]);

    return $intervention;
}

it('renders dynamic fields from the template schema', function () {
    $intervention = makeReportTemplateContext();

    Livewire::test(FillInterventionReportPage::class, ['intervention_id' => $intervention->id])
        ->assertFormFieldExists('constat')
        ->assertFormFieldExists('pieces')
        ->assertFormFieldExists('etat')
        ->assertFormFieldExists('conforme')
        ->assertFormFieldExists('commentaire')
        ->assertFormFieldExists('date_intervention')
        ->assertFormFieldExists('piece_jointe');
});

it('refuses access when the intervention is not assigned to the technician', function () {
    $intervention = Intervention::factory()->create([
        'company_id' => test()->company->id,
        'third_party_id' => test()->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::EN_COURS,
    ]);

    Livewire::test(FillInterventionReportPage::class, ['intervention_id' => $intervention->id])
        ->assertStatus(404);
});

it('displays the fallback message when no active template matches', function () {
    $intervention = makeReportTemplateContext();
    InterventionReportTemplate::where('name', 'Rapport SAV')->update(['is_active' => false]);

    Livewire::test(FillInterventionReportPage::class, ['intervention_id' => $intervention->id])
        ->assertSee('Aucun modèle de rapport actif ne correspond au type de cette intervention.');
});

it('saves the report data and links the template on submit', function () {
    $intervention = makeReportTemplateContext();
    $template = InterventionReportTemplate::where('name', 'Rapport SAV')->firstOrFail();

    Livewire::test(FillInterventionReportPage::class, ['intervention_id' => $intervention->id])
        ->fillForm([
            'constat' => 'Moteur remplacé',
            'pieces' => 2,
            'etat' => 'OK',
            'conforme' => true,
        ])
        ->call('submit');

    $intervention->refresh();

    expect($intervention->report_data['constat'])->toBe('Moteur remplacé')
        ->and($intervention->report_data['etat'])->toBe('OK')
        ->and($intervention->report_template_id)->toBe($template->id);
});

it('rejects submit when a required field is missing', function () {
    $intervention = makeReportTemplateContext();

    Livewire::test(FillInterventionReportPage::class, ['intervention_id' => $intervention->id])
        ->fillForm([
            'constat' => '',
            'pieces' => 2,
            'etat' => 'OK',
            'conforme' => true,
        ])
        ->call('submit')
        ->assertHasFormErrors(['constat' => 'required']);
});
