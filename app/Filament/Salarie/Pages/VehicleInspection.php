<?php

namespace App\Filament\Salarie\Pages;

use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleConditionReport;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleInspection extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Phosphor::FileText;

    protected static ?string $title = 'État des lieux du véhicule';

    protected string $view = 'filament.salarie.pages.vehicle-inspection';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'vehicules/{uuid}/inspection';

    public ?Vehicle $vehicle = null;

    public ?VehicleAssignment $assignment = null;

    public ?array $data = [];

    public function mount(string $uuid): void
    {
        $this->vehicle = Vehicle::where('uuid', $uuid)->firstOrFail();

        $this->assignment = $this->vehicle->currentAssignment;

        if (! $this->assignment) {
            abort(403, 'Aucune affectation active pour ce véhicule.');
        }

        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Identification')
                        ->description('Veuillez vous identifier avec votre code PIN.')
                        ->icon(Phosphor::Lock)
                        ->schema([
                            TextInput::make('pin_code')
                                ->label('Votre Code PIN')
                                ->password()
                                ->required()
                                ->rule(function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $employee = $this->assignment->employee;
                                        if (auth()->user()->salarie->id !== $employee->id) {
                                            $fail("Vous n'êtes pas le conducteur assigné à ce véhicule.");

                                            return;
                                        }

                                        if (! Hash::check($value, $employee->pin_code)) {
                                            $fail('Le code PIN est incorrect.');
                                        }
                                    };
                                }),

                            Hidden::make('type')
                                ->default(ConditionReportType::CHECK_OUT->value),
                        ]),

                    Wizard\Step::make('Informations')
                        ->description('Kilométrage et Carburant')
                        ->icon(Phosphor::Screencast)
                        ->schema([
                            TextInput::make('odometer')
                                ->label('Kilométrage actuel')
                                ->numeric()
                                ->required()
                                ->suffix('km')
                                ->default($this->assignment->start_odometer ?? $this->vehicle->odometer),

                            TextInput::make('fuel_level')
                                ->label('Niveau de carburant (0-100)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(100)
                                ->suffix('%')
                                ->required(),
                        ]),

                    Wizard\Step::make('Photos')
                        ->description('Prenez en photo les 4 côtés et le tableau de bord.')
                        ->icon('heroicon-o-camera')
                        ->schema([
                            Section::make('Extérieur')
                                ->schema([
                                    FileUpload::make('photo_front')
                                        ->label('Face Avant')
                                        ->image()
                                        ->imageEditor()
                                        ->required()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024'),
                                    FileUpload::make('photo_back')
                                        ->label('Face Arrière')
                                        ->image()
                                        ->imageEditor()
                                        ->required()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024'),
                                    FileUpload::make('photo_left')
                                        ->label('Côté Gauche')
                                        ->image()
                                        ->imageEditor()
                                        ->required()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024'),
                                    FileUpload::make('photo_right')
                                        ->label('Côté Droit')
                                        ->image()
                                        ->imageEditor()
                                        ->required()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024'),
                                ]),
                            Section::make('Intérieur')
                                ->schema([
                                    FileUpload::make('photo_dashboard')
                                        ->label('Tableau de bord (Compteurs allumés)')
                                        ->image()
                                        ->imageEditor()
                                        ->required()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024'),
                                ]),
                        ]),

                    Wizard\Step::make('Validation')
                        ->description('Commentaires et signature')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Textarea::make('comment')
                                ->label('Commentaire / Signalement de dommages')
                                ->placeholder('Ex: Rayure sur la portière avant gauche, voyant moteur allumé...')
                                ->rows(4),
                            Checkbox::make('signature')
                                ->label('Je certifie l\'exactitude de ces informations')
                                ->required()
                                ->accepted(),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button type="submit" wire:click="submit" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">Soumettre l\'état des lieux</button>')),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $report = VehicleConditionReport::create([
            'vehicle_assignment_id' => $this->assignment->id,
            'type' => ConditionReportType::CHECK_OUT,
            'odometer' => $data['odometer'],
            'fuel_level' => $data['fuel_level'],
            'comment' => $data['comment'],
            'signed_at' => now(),
            'signature_checksum' => hash('sha256', auth()->user()->id.'-'.now()->timestamp),
        ]);

        $collections = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_dashboard'];
        foreach ($collections as $collection) {
            if (! empty($data[$collection])) {
                $path = storage_path('app/public/'.$data[$collection]);
                if (file_exists($path)) {
                    $report->addMedia($path)->toMediaCollection($collection);
                }
            }
        }

        $this->assignment->update([
            'start_odometer' => $data['odometer'],
        ]);

        Notification::make()
            ->title('État des lieux validé avec succès !')
            ->success()
            ->send();

        $this->redirect(route('filament.salarie.pages.dashboard'));
    }
}
