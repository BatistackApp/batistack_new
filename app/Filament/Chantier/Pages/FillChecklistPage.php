<?php

namespace App\Filament\Chantier\Pages;

use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ChecklistSubmission;
use App\Models\Chantiers\ChecklistTemplate;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class FillChecklistPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.chantier.pages.fill-checklist-page';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public $template_id;

    public $task_id;

    public $template;

    public $chantierTask;

    public function mount()
    {
        $this->template_id = request()->query('template_id');
        $this->task_id = request()->query('task_id');

        if (! $this->template_id || ! $this->task_id) {
            abort(404);
        }

        $this->template = ChecklistTemplate::findOrFail($this->template_id);
        $this->chantierTask = ChantierTask::findOrFail($this->task_id);

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $schemaComponents = [];

        if ($this->template && $this->template->schema) {
            foreach ($this->template->schema as $block) {
                $blockData = $block['data'];
                $blockType = $block['type'];

                if ($blockType === 'text_input') {
                    $field = Components\TextInput::make($blockData['name'])
                        ->label($blockData['label'])
                        ->required($blockData['required'] ?? false);
                    $schemaComponents[] = $field;
                } elseif ($blockType === 'checkbox') {
                    $field = Components\Checkbox::make($blockData['name'])
                        ->label($blockData['label'])
                        ->required($blockData['required'] ?? false);
                    $schemaComponents[] = $field;
                } elseif ($blockType === 'file_upload') {
                    $field = Components\FileUpload::make($blockData['name'])
                        ->label($blockData['label'])
                        ->image()
                        ->imageEditor()
                        ->required($blockData['required'] ?? false);
                    $schemaComponents[] = $field;
                }
            }
        }

        // Add Signature Field
        $schemaComponents[] = Section::make('Signature')
            ->schema([
                SignaturePad::make('signature')
                    ->label('Signature numérique')
                    ->required(),
            ]);

        return $schema
            ->components($schemaComponents)
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();

        $signature = $data['signature'];
        unset($data['signature']);

        ChecklistSubmission::create([
            'checklist_template_id' => $this->template_id,
            'chantier_task_id' => $this->task_id,
            'submitted_by' => Auth::id(),
            'data' => $data,
            'signature_path' => $signature,
        ]);

        $chantierId = $this->chantierTask->phase->chantier_id;

        ChantierLog::create([
            'chantier_id' => $chantierId,
            'user_id' => Auth::id(),
            'date' => now(),
            'content' => 'Checklist dynamique "'.$this->template->name.'" complétée pour la tâche "'.$this->chantierTask->label.'".',
            'incident_reported' => false,
        ]);

        Notification::make()
            ->title('Checklist enregistrée avec succès')
            ->success()
            ->send();

        $chantierId = $this->chantierTask->phase->chantier_id;

        return redirect()->to('/chantier/chantiers/'.$chantierId.'/edit');
    }
}
