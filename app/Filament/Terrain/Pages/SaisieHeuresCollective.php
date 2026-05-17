<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use BackedEnum;
use DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SaisieHeuresCollective extends Page
{
    protected string $view = 'filament.terrain.pages.saisie-heures-collective';

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Timer;

    protected static ?string $title = 'Saisie Collective des Heures';

    protected static ?string $navigationLabel = 'Pointage Equipe';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $employee = auth()->user()->salarie;

        // Requête pour ne récupérer que les chantiers gérés ou assignés à ce chef
        $chantiersQuery = Chantier::query()
            ->where('manager_id', $employee->id)
            ->orWhereHas('members', fn ($q) => $q->where('employees.id', $employee->id))
            ->pluck('name', 'id');

        return $schema
            ->components([
                Section::make('Contexte de la Journée')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date d\'intervention')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Select::make('chantier_id')
                            ->label('Chantier concerné')
                            ->options($chantiersQuery)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $this->loadTeamMembers($state, $set)),
                    ])->columns(2),

                Section::make('Pointage de l\'Équipe')
                    ->description('Saisissez les heures de travail et de trajet pour chaque membre présent.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('time_entries')
                            ->label('Compagnons présents')
                            ->schema([
                                Select::make('employee_id')
                                    ->label('Collaborateur')
                                    ->options(Employee::where('is_active', true)->pluck('last_name', 'id'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->required()
                                    ->disabled() // Verrouillé pour éviter les erreurs d'affectation
                                    ->dehydrated(),

                                TextInput::make('hours')
                                    ->label('Heures Production')
                                    ->numeric()
                                    ->default(7.0)
                                    ->suffix('h')
                                    ->required(),

                                TextInput::make('travel_hours')
                                    ->label('Temps Route')
                                    ->numeric()
                                    ->default(0.5)
                                    ->suffix('h'),
                            ])
                            ->columns(3)
                            ->addable(false) // On n'autorise pas l'ajout manuel hors équipe
                            ->deletable(true) // On peut supprimer si un ouvrier était absent
                            ->reorderable(false),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Charge automatiquement les membres de l'équipe du chantier sélectionné.
     */
    protected function loadTeamMembers(?int $chantierId, callable $set): void
    {
        if (! $chantierId) {
            return;
        }

        $chantier = Chantier::with('members')->find($chantierId);
        if (! $chantier) {
            return;
        }

        $entries = $chantier->members->map(fn ($member) => [
            'employee_id' => $member->id,
            'hours' => 7.0,
            'travel_hours' => 0.5,
        ])->toArray();

        $set('time_entries', $entries);
    }

    /**
     * Traitement et enregistrement du lot de pointages.
     */
    public function save(): void
    {
        $state = $this->form->getState();

        if (empty($state['time_entries'])) {
            Notification::make()->title('Aucun pointage à enregistrer')->danger()->send();

            return;
        }

        DB::transaction(function () use ($state) {
            foreach ($state['time_entries'] as $entry) {
                // On crée une ligne de pointage individuelle
                TimeEntry::create([
                    'employee_id' => $entry['employee_id'],
                    'chantier_id' => $state['chantier_id'],
                    'date' => $state['date'],
                    'hours' => $entry['hours'],
                    'travel_hours' => $entry['travel_hours'] ?? 0,
                    'type' => TimeEntryType::NORMAL,
                    'status' => TimeEntryStatus::DRAFT, // Soumis à la validation du conducteur de travaux
                ]);
            }
        });

        Notification::make()
            ->title('Pointage enregistré')
            ->body('Les heures de l\'équipe ont été envoyées pour validation.')
            ->success()
            ->send();

        $this->redirect('/terrain');
    }
}
