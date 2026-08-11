<?php

namespace App\Filament\RH\Pages;

use App\Enums\RH\EquipementStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\RH\EquipementAssignment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ScanEquipementPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-viewfinder-circle';
    protected static ?string $navigationLabel = 'Scan Outillage (NFC)';
    protected static ?string $title = 'Scanner un Outillage';
    protected static string|null|\UnitEnum $navigationGroup = 'Gestion';
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.rh.pages.scan-equipement-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informations de Scan')
                    ->columnSpanFull()
                    ->schema([
                        BarcodeInput::make('barcode')
                            ->label('Code NFC / Code Barre')
                            ->placeholder('Scannez l\'étiquette de l\'outil...')
                            ->autofocus()
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $set) => $this->checkEquipement($state, $set)),

                        ToggleButtons::make('action_type')
                            ->label('Action à réaliser')
                            ->options([
                                'borrow' => 'Emprunter / Assigner',
                                'return' => 'Rendre / Restituer',
                            ])
                            ->colors([
                                'borrow' => 'warning',
                                'return' => 'success',
                            ])
                            ->inline()
                            ->required()
                            ->visible(fn (callable $get) => filled($get('equipement_id'))),

                        Select::make('employee_id')
                            ->label('Technicien Emprunteur')
                            ->options(Employee::active()->pluck('first_name', 'id')->map(function ($name, $id) {
                                return Employee::find($id)->full_name;
                            }))
                            ->searchable()
                            ->required(fn (callable $get) => $get('action_type') === 'borrow')
                            ->visible(fn (callable $get) => $get('action_type') === 'borrow'),

                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier de destination (Optionnel)')
                            ->options(Chantier::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (callable $get) => $get('action_type') === 'borrow'),

                        TextInput::make('notes')->label('Notes')
                            ->label('Observations (État, pannes, etc.)')
                            ->visible(fn (callable $get) => filled($get('equipement_id'))),
                    ])
                    ->columns(1)
                    ->maxWidth('xl')
            ])
            ->statePath('data');
    }

    protected function checkEquipement($barcode, callable $set)
    {
        if (blank($barcode)) {
            $set('equipement_id', null);
            $set('equipement_info', null);
            return;
        }

        $equipement = Equipement::where('barcode', $barcode)
            ->orWhere('serial_number', $barcode)
            ->first();

        if ($equipement) {
            $set('equipement_id', $equipement->id);
            $set('equipement_info', "Outil détecté : {$equipement->getLabel()} - Statut actuel : {$equipement->status->getLabel()}");

            if ($equipement->status === EquipementStatus::AVAILABLE) {
                $set('action_type', 'borrow');
            } else {
                $set('action_type', 'return');
                if ($equipement->employee_id) {
                    $set('employee_id', $equipement->employee_id);
                }
            }
        } else {
            $set('equipement_id', null);
            $set('equipement_info', "⚠️ Outil introuvable pour ce code.");
        }
    }

    public function submit()
    {
        $data = $this->form->getState();

        $equipement = Equipement::find($data['equipement_id']);

        if (! $equipement) {
            Notification::make()->danger()->title('Outil introuvable')->send();
            return;
        }

        DB::transaction(function () use ($data, $equipement) {
            if ($data['action_type'] === 'borrow') {
                // Fermer l'ancien prêt si existant (sécurité)
                if ($current = $equipement->currentAssignment) {
                    $current->update(['returned_at' => now(), 'notes' => 'Clôture auto via nouvel emprunt']);
                }

                EquipementAssignment::create([
                    'equipement_id' => $equipement->id,
                    'employee_id' => $data['employee_id'],
                    'chantier_id' => $data['chantier_id'] ?? null,
                    'assigned_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]);

                $equipement->update([
                    'status' => EquipementStatus::IN_USE,
                    'employee_id' => $data['employee_id'],
                ]);

                Notification::make()->success()->title('Outil emprunté avec succès')->send();
            } else {
                // Retour
                if ($current = $equipement->currentAssignment) {
                    $current->update([
                        'returned_at' => now(),
                        'notes' => $data['notes'] ?? null,
                    ]);
                }

                $equipement->update([
                    'status' => EquipementStatus::AVAILABLE,
                    'employee_id' => null, // Plus affecté
                ]);

                Notification::make()->success()->title('Outil restitué avec succès')->send();
            }
        });

        $this->form->fill();
    }
}
