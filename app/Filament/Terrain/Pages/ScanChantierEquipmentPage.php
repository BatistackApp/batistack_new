<?php

namespace App\Filament\Terrain\Pages;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\RH\Equipement;
use App\Models\Immobilisation\FixedAsset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ScanChantierEquipmentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::QrCode;

    protected static ?string $navigationLabel = 'Scan Matériel';

    protected static ?string $title = 'Scanner du Matériel';

    protected static string|null|\UnitEnum $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.terrain.pages.scan-chantier-equipment';

    public ?array $data = [];

    public ?string $scanResult = null;

    public ?string $scanError = null;

    public function mount(): void
    {
        $this->form->fill([
            'chantier_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Scan du Matériel')
                    ->schema([
                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->options(fn () => Chantier::forEmployee(Auth::user()->salarie ?? Auth::user()->employee ?? null)
                                ->select('id', 'name')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        BarcodeInput::make('qr_code')
                            ->label('QR Code / Code-barres')
                            ->placeholder('Scannez le code du matériel...')
                            ->autofocus()
                            ->capture('environment')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $set) => $this->handleScan($state, $set)),

                        Textarea::make('notes')
                            ->label('Notes (optionnel)')
                            ->rows(2)
                            ->placeholder('Observations...')
                            ->visible(fn (callable $get) => filled($get('trackable_id'))),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function handleScan(string $code, callable $set): void
    {
        $this->scanResult = null;
        $this->scanError = null;

        if (blank($code)) {
            $set('trackable_id', null);
            $set('trackable_type', null);
            $set('trackable_info', null);
            $set('action_type', null);

            return;
        }

        // Resolve code against FixedAsset first, then Equipement
        $fixedAsset = FixedAsset::where('qr_token', $code)
            ->orWhere('serial_number', $code)
            ->first();

        if ($fixedAsset) {
            $set('trackable_id', $fixedAsset->id);
            $set('trackable_type', FixedAsset::class);
            $set('trackable_info', "Gros matériel : {$fixedAsset->name} ({$fixedAsset->reference})");
            $this->determineAction($fixedAsset, FixedAsset::class, $set);

            return;
        }

        $equipement = Equipement::where('qr_token', $code)
            ->orWhere('serial_number', $code)
            ->orWhere('barcode', $code)
            ->first();

        if ($equipement) {
            $set('trackable_id', $equipement->id);
            $set('trackable_type', Equipement::class);
            $set('trackable_info', "Outillage : {$equipement->getLabel()}");
            $this->determineAction($equipement, Equipement::class, $set);

            return;
        }

        $set('trackable_id', null);
        $set('trackable_type', null);
        $set('trackable_info', null);
        $set('action_type', null);
        $this->scanError = "Code introuvable : {$code}";
    }

    protected function determineAction($model, string $type, callable $set): void
    {
        $chantierId = $this->form->getState()['chantier_id'] ?? null;

        // Check if there's an open tracking for this equipment on any chantier
        $openTracking = ChantierEquipmentTracking::where('trackable_type', $type)
            ->where('trackable_id', $model->id)
            ->whereNull('check_out_at')
            ->first();

        if ($openTracking) {
            $set('action_type', 'check_out');
            $set('existing_tracking_id', $openTracking->id);
            $set('current_chantier', $openTracking->chantier?->name ?? 'Inconnu');
        } else {
            $set('action_type', 'check_in');
            $set('existing_tracking_id', null);
            $set('current_chantier', null);
        }
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        if (blank($data['trackable_id']) || blank($data['trackable_type'])) {
            Notification::make()->danger()->title('Aucun matériel identifié')->send();

            return;
        }

        if (blank($data['chantier_id'])) {
            Notification::make()->danger()->title('Veuillez sélectionner un chantier')->send();

            return;
        }

        $action = $data['action_type'] ?? null;

        if ($action === 'check_in') {
            $this->checkIn($data);
        } elseif ($action === 'check_out') {
            $this->checkOut($data);
        }
    }

    protected function checkIn(array $data): void
    {
        $tracking = ChantierEquipmentTracking::create([
            'chantier_id' => $data['chantier_id'],
            'trackable_type' => $data['trackable_type'],
            'trackable_id' => $data['trackable_id'],
            'scanned_by' => Auth::id(),
            'check_in_at' => now(),
            'qr_token' => $data['qr_code'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $label = $tracking->getTrackableLabel();

        Notification::make()
            ->success()
            ->title("{$label} marqué présent")
            ->body("Arrivée enregistrée sur le chantier.")
            ->send();

        $this->scanResult = "✓ {$label} — Arrivée enregistrée";
        $this->form->fill(['chantier_id' => $data['chantier_id']]);
    }

    protected function checkOut(array $data): void
    {
        $trackingId = $data['existing_tracking_id'] ?? null;

        if (! $trackingId) {
            Notification::make()->danger()->title('Aucune session ouverte trouvée')->send();

            return;
        }

        $tracking = ChantierEquipmentTracking::findOrFail($trackingId);
        $tracking->update([
            'check_out_at' => now(),
            'notes' => $data['notes'] ?? $tracking->notes,
        ]);

        $label = $tracking->getTrackableLabel();
        $duration = $tracking->getDurationInDays();
        $cost = $tracking->getImmobilizationCost();

        Notification::make()
            ->success()
            ->title("{$label} marqué absent")
            ->body("Durée : {$duration} jour(s) — Coût : ".number_format($cost, 2, ',', ' ').' €')
            ->send();

        $this->scanResult = "✓ {$label} — Départ enregistré ({$duration}j, ".number_format($cost, 2, ',', ' ').' €)';
        $this->form->fill(['chantier_id' => $data['chantier_id']]);
    }

    public function getTodayPresences(): \Illuminate\Support\Collection
    {
        $chantierId = $this->data['chantier_id'] ?? null;

        $query = ChantierEquipmentTracking::with('trackable', 'chantier')
            ->whereDate('check_in_at', today());

        if ($chantierId) {
            $query->where('chantier_id', $chantierId);
        }

        return $query->latest('check_in_at')->get();
    }
}
