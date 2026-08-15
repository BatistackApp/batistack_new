<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierReserve;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SignalReservePage extends Page
{
    protected string $view = 'filament.terrain.pages.signal-reserve';

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Warning;

    protected static ?string $title = 'Signaler une Réserve';

    protected static ?string $navigationLabel = 'Réserve / OPR';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['severity' => ReserveSeverity::MINOR->value]);
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
                Section::make('Signalement d\'un défaut')
                    ->description('Un défaut est constaté, vous le signalez pour qu\'il soit traité et levé.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('chantier_id')
                            ->label('Chantier concerné')
                            ->options($chantiersQuery)
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->label('Objet de la réserve')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Select::make('severity')
                            ->label('Gravité')
                            ->options(ReserveSeverity::class)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->label('Photos du défaut')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            $reserve = ChantierReserve::create([
                'chantier_id' => $data['chantier_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'severity' => $data['severity'],
                'status' => ChantierReserveStatus::OPEN,
            ]);

            $this->form->model($reserve);
            $this->form->saveRelationships();

            ChantierLog::create([
                'chantier_id' => $reserve->chantier_id,
                'user_id' => Auth::id(),
                'date' => now(),
                'content' => 'Réserve signalée : "'.$reserve->title.'" ('.$reserve->reference.').',
                'incident_reported' => true,
            ]);

            Notification::make()
                ->title('Réserve signalée')
                ->body('Référence : '.$reserve->reference)
                ->success()
                ->send();

            $this->redirect('/terrain');
        } catch (\Throwable $e) {
            if (isset($reserve) && $reserve->exists) {
                $reserve->clearMediaCollection('photos');
                $reserve->delete();
            }

            Notification::make()
                ->title('Erreur')
                ->body('Le signalement de la réserve a échoué : '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
