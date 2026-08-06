<?php

use App\Models\Chantiers\ChecklistTemplate;
use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ChecklistSubmission;
use App\Models\User;
use Livewire\Livewire;
use App\Filament\Chantier\Pages\FillChecklistPage;
use Illuminate\Support\Facades\Auth;

it('can render fill checklist page and submit data', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $template = ChecklistTemplate::create([
        'name' => 'Audit Test',
        'is_active' => true,
        'schema' => [
            [
                'type' => 'text_input',
                'data' => [
                    'name' => 'comment',
                    'label' => 'Commentaire',
                    'required' => true,
                ]
            ]
        ]
    ]);

    $task = ChantierTask::factory()->create();

    Livewire::withQueryParams([
        'template_id' => $template->id,
        'task_id' => $task->id,
    ])
        ->test(FillChecklistPage::class)
        ->assertSuccessful()
        ->fillForm([
            'comment' => 'Tout est conforme',
            'signature' => 'data:image/png;base64,1234567890'
        ])
        ->call('submit')
        ->assertRedirect('/chantier/chantier-tasks/' . $task->id)
        ->assertNotified();

    $this->assertDatabaseHas('checklist_submissions', [
        'checklist_template_id' => $template->id,
        'chantier_task_id' => $task->id,
        'submitted_by' => $user->id,
        'signature_path' => 'data:image/png;base64,1234567890'
    ]);
});
