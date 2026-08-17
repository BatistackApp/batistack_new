<?php

namespace App\Filament\Technicien\Pages;

use App\Enums\Core\SignatureStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionReportTemplate;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FillInterventionReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.technicien.pages.fill-intervention-report-page';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?int $intervention_id = null;

    public ?Intervention $intervention = null;

    public ?InterventionReportTemplate $template = null;

    public function mount(?int $intervention_id = null): void
    {
        $this->intervention_id = $intervention_id ?: (int) request()->query('intervention_id');

        if (! $this->intervention_id) {
            abort(404);
        }

        $salarieId = auth()->user()?->salarie?->id;

        $this->intervention = Intervention::query()
            ->whereHas('workers', function ($query) use ($salarieId) {
                $query->where('employee_id', $salarieId);
            })
            ->first();

        if (! $this->intervention) {
            abort(404);
        }

        $this->template = $this->intervention->applicableReportTemplate();

        if ($this->template) {
            $this->form->fill($this->intervention->report_data ?? []);
        }
    }

    public function form(Schema $schema): Schema
    {
        $schemaComponents = [];

        if ($this->template && is_array($this->template->schema)) {
            foreach ($this->template->schema as $block) {
                $blockData = $block['data'] ?? [];
                $blockType = $block['type'] ?? null;
                $name = $blockData['name'] ?? null;

                if (! $name) {
                    continue;
                }

                $field = $this->buildField($blockType, $name, $blockData);

                if ($field) {
                    $schemaComponents[] = $field;
                }
            }
        }

        if ($this->template) {
            $schemaComponents[] = Section::make('Informations')
                ->schema([
                    Components\TextInput::make('_completed_by')
                        ->label('Rapport rempli par')
                        ->default(fn () => auth()->user()?->name)
                        ->disabled()
                        ->dehydrated(false),
                ]);
        }

        return $schema
            ->components($schemaComponents)
            ->statePath('data');
    }

    protected function buildField(?string $blockType, string $name, array $blockData): ?Components\Field
    {
        $label = $blockData['label'] ?? $name;
        $required = (bool) ($blockData['required'] ?? false);

        return match ($blockType) {
            'text_input' => Components\TextInput::make($name)
                ->label($label)
                ->required($required),
            'textarea' => Components\Textarea::make($name)
                ->label($label)
                ->required($required),
            'number' => Components\TextInput::make($name)
                ->label($label)
                ->numeric()
                ->minValue(isset($blockData['min']) && $blockData['min'] !== '' ? (int) $blockData['min'] : null)
                ->maxValue(isset($blockData['max']) && $blockData['max'] !== '' ? (int) $blockData['max'] : null)
                ->required($required),
            'checkbox' => Components\Checkbox::make($name)
                ->label($label)
                ->required($required),
            'select' => Components\Select::make($name)
                ->label($label)
                ->options($this->parseOptions($blockData['options'] ?? null))
                ->required($required),
            'date' => Components\DatePicker::make($name)
                ->label($label)
                ->required($required),
            'file_upload' => Components\FileUpload::make($name)
                ->label($label)
                ->disk('public')
                ->directory('interventions/reports')
                ->required($required),
            default => null,
        };
    }

    protected function parseOptions(?string $options): array
    {
        if (blank($options)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $options))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->mapWithKeys(fn ($option) => [$option => $option])
            ->all();
    }

    public function submit(): void
    {
        if (! $this->canClose()) {
            Notification::make()
                ->title('Intervention verrouillée')
                ->body('Le rapport ne peut plus être modifié : l\'intervention n\'est plus en cours.')
                ->warning()
                ->send();

            return;
        }

        if ($this->intervention->signatures()->where('status', SignatureStatus::SIGNED)->exists()) {
            Notification::make()
                ->title('Intervention signée')
                ->body('Le rapport ne peut plus être modifié : l\'intervention a déjà été signée.')
                ->warning()
                ->send();

            return;
        }

        if (! $this->template) {
            Notification::make()
                ->title('Aucun modèle de rapport')
                ->body('Aucun modèle actif ne correspond au type de cette intervention.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $this->intervention->update([
            'report_template_id' => $this->template->id,
            'report_data' => $data,
        ]);

        Notification::make()
            ->title('Rapport enregistré')
            ->body('Le rapport d\'intervention a été enregistré avec succès.')
            ->success()
            ->send();

        $this->redirect('/technicien/interventions/'.$this->intervention->id.'/edit');
    }

    public function getInterventionStatusLabel(): string
    {
        return $this->intervention->status->getLabel();
    }

    public function canClose(): bool
    {
        return $this->intervention->status === InterventionStatus::EN_COURS;
    }
}
