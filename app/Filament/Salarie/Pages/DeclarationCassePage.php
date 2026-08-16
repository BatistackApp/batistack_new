<?php

namespace App\Filament\Salarie\Pages;

use App\Enums\Immobilisation\TicketSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Equipement;
use App\Services\Immobilisation\AssetMaintenanceTicketService;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DeclarationCassePage extends Page
{
    protected string $view = 'filament.salarie.pages.declaration-casse';

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Warning;

    protected static ?string $title = 'Déclarer une casse / sinistre';

    protected static ?string $navigationLabel = 'Déclarer une casse';

    protected static string|null|\UnitEnum $navigationGroup = 'Outils';

    protected static ?int $navigationSort = 60;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return parent::canAccess() && auth()->user()?->salarie !== null;
    }

    public function mount(): void
    {
        $this->form->fill(['severity' => TicketSeverity::MEDIUM->value]);
    }

    public function form(Schema $schema): Schema
    {
        $employee = auth()->user()->salarie;

        $chantiersQuery = Chantier::query()
            ->where(fn ($q) => $q
                ->where('manager_id', $employee->id)
                ->orWhereHas('members', fn ($q) => $q->where('employees.id', $employee->id)))
            ->pluck('name', 'id');

        return $schema
            ->components([
                Section::make('Détection de l\'outil')
                    ->description('Scannez le QR code apposé sur l\'outil ou saisissez son numéro de série.')
                    ->columnSpanFull()
                    ->schema([
                        BarcodeInput::make('code')
                            ->label('QR Code / Numéro de série')
                            ->placeholder('Scannez l\'étiquette...')
                            ->autofocus()
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $set) => $this->checkAsset($state, $set)),
                        TextInput::make('asset_display')
                            ->label('Outil détecté')
                            ->disabled()
                            ->dehydrated(false),
                        Hidden::make('asset_type'),
                        Hidden::make('asset_id'),
                    ]),
                Section::make('Déclaration de casse')
                    ->description('Décrivez le sinistre constaté. Le dépôt sera notifié immédiatement.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('severity')
                            ->label('Gravité')
                            ->options(TicketSeverity::class)
                            ->default(TicketSeverity::MEDIUM->value)
                            ->required(),
                        Select::make('chantier_id')
                            ->label('Chantier concerné (optionnel)')
                            ->options($chantiersQuery)
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->label('Description du sinistre')
                            ->required()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->label('Photos')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function checkAsset($code, callable $set): void
    {
        if (blank($code)) {
            $set('asset_type', null);
            $set('asset_id', null);
            $set('asset_display', null);

            return;
        }

        $asset = app(AssetMaintenanceTicketService::class)->resolveByCode($code);

        if ($asset) {
            $set('asset_type', $asset::class);
            $set('asset_id', $asset->getKey());
            $set('asset_display', $asset instanceof Equipement ? $asset->getLabel() : $asset->name);
        } else {
            $set('asset_type', null);
            $set('asset_id', null);
            $set('asset_display', 'Aucun outil trouvé pour ce code.');
        }
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $asset = $this->resolveDetectedAsset($data);

        if (! $asset) {
            Notification::make()
                ->title('Aucun outil détecté')
                ->body('Scannez d\'abord le QR code de l\'outil.')
                ->warning()
                ->send();

            return;
        }

        $employee = auth()->user()->salarie;

        try {
            $service = app(AssetMaintenanceTicketService::class);

            $ticket = $service->create($asset, $employee, $data);

            $this->form->model($ticket);
            $this->form->saveRelationships();

            $service->notifyDepot($ticket);

            Notification::make()
                ->title('Casse déclarée')
                ->body('Référence : '.$ticket->reference)
                ->success()
                ->send();

            $this->redirect('/salarie');
        } catch (\Throwable $e) {
            if (isset($ticket) && $ticket->exists) {
                $ticket->clearMediaCollection('photos');
                $ticket->delete();
            }

            Notification::make()
                ->title('Erreur')
                ->body('La déclaration a échoué : '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function resolveDetectedAsset(array $data): FixedAsset|Equipement|null
    {
        $type = $data['asset_type'] ?? null;

        if (! in_array($type, [FixedAsset::class, Equipement::class], true)) {
            return null;
        }

        if (blank($data['asset_id'] ?? null)) {
            return null;
        }

        $asset = $type::find($data['asset_id']);

        return $asset instanceof FixedAsset || $asset instanceof Equipement ? $asset : null;
    }
}
