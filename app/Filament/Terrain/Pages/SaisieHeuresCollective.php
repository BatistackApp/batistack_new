<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use BackedEnum;
use Carbon\Carbon;
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

    public ?array $recentEntries = [];

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $employee = auth()->user()->salarie;

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
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->loadRecentEntries()),

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
                                    ->disabled()
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
                            ->addable(false)
                            ->deletable(true)
                            ->reorderable(false),
                    ]),

                Section::make('Historique récent')
                    ->description('5 derniers jours de pointage pour ce chantier')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Placeholder::make('history_display')
                            ->content(fn () => $this->getHistoryHtml()),
                    ])
                    ->visible(fn () => ! empty($this->recentEntries)),
            ])
            ->statePath('data');
    }

    protected function loadTeamMembers(?int $chantierId, callable $set): void
    {
        if (! $chantierId) {
            $this->recentEntries = [];

            return;
        }

        $chantier = Chantier::with('members')->find($chantierId);
        if (! $chantier) {
            return;
        }

        // Try to pre-fill from yesterday
        $yesterdayEntries = TimeEntry::where('chantier_id', $chantierId)
            ->where('date', now()->subDay()->toDateString())
            ->where('employee_id', '!=', null)
            ->get()
            ->keyBy('employee_id');

        $entries = $chantier->members->map(function ($member) use ($yesterdayEntries) {
            $yesterday = $yesterdayEntries->get($member->id);

            return [
                'employee_id' => $member->id,
                'hours' => $yesterday?->hours ?? 7.0,
                'travel_hours' => $yesterday?->travel_hours ?? 0.5,
            ];
        })->toArray();

        $set('time_entries', $entries);

        $this->loadRecentEntries();
    }

    protected function loadRecentEntries(): void
    {
        $state = $this->form->getState();
        $chantierId = $state['chantier_id'] ?? null;

        if (! $chantierId) {
            $this->recentEntries = [];

            return;
        }

        $this->recentEntries = TimeEntry::where('chantier_id', $chantierId)
            ->where('date', '>=', now()->subDays(5)->toDateString())
            ->with('employee')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    protected function getHistoryHtml(): string
    {
        if (empty($this->recentEntries)) {
            return '<p class="text-sm text-gray-500">Aucun historique récent.</p>';
        }

        $html = '<div class="space-y-2">';
        $grouped = collect($this->recentEntries)->groupBy('date');

        foreach ($grouped as $date => $entries) {
            $totalHours = collect($entries)->sum('hours');
            $status = $entries[0]['status'] ?? 'draft';
            $statusLabel = match ($status) {
                'draft' => 'Brouillon',
                'submitted' => 'En attente',
                'approved' => 'Validé',
                default => $status,
            };
            $statusColor = match ($status) {
                'draft' => 'gray',
                'submitted' => 'warning',
                'approved' => 'success',
                default => 'gray',
            };

            $html .= '<div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800">';
            $html .= '<span class="text-sm font-medium">'.Carbon::parse($date)->format('d/m/Y').'</span>';
            $html .= '<span class="text-sm text-gray-600">'.number_format($totalHours, 1, ',', ' ').'h — '.count($entries).' personne(s)</span>';
            $html .= '<span class="px-2 py-0.5 text-xs rounded-full bg-'.$statusColor.'-100 text-'.$statusColor.'-800">'.$statusLabel.'</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function save(): void
    {
        $this->createEntries(TimeEntryStatus::DRAFT);
    }

    public function saveAndSubmit(): void
    {
        $this->createEntries(TimeEntryStatus::SUBMITTED);
    }

    protected function createEntries(TimeEntryStatus $status): void
    {
        $state = $this->form->getState();

        if (empty($state['time_entries'])) {
            Notification::make()->title('Aucun pointage à enregistrer')->danger()->send();

            return;
        }

        DB::transaction(function () use ($state, $status) {
            foreach ($state['time_entries'] as $entry) {
                TimeEntry::create([
                    'employee_id' => $entry['employee_id'],
                    'chantier_id' => $state['chantier_id'],
                    'date' => $state['date'],
                    'hours' => $entry['hours'],
                    'travel_hours' => $entry['travel_hours'] ?? 0,
                    'type' => TimeEntryType::NORMAL,
                    'status' => $status,
                ]);
            }
        });

        $label = $status === TimeEntryStatus::SUBMITTED ? 'envoyés pour validation' : 'enregistrés en brouillon';

        Notification::make()
            ->title('Pointages '.$label)
            ->body('Les heures de l\'équipe ont été '.$label.'.')
            ->success()
            ->send();

        $this->redirect('/terrain');
    }
}
