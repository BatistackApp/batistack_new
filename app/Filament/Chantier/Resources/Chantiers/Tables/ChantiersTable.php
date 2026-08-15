<?php

namespace App\Filament\Chantier\Resources\Chantiers\Tables;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Flottes\AssignmentStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Chantiers\ChantierDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')->label('Nom')
                    ->label('Chantier')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->client->name),
                TextColumn::make('status')->label('Statut')
                    ->label('État')
                    ->badge(),
                TextColumn::make('progress')
                    ->label('Avancement')
                    ->getStateUsing(fn (Chantier $record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'].' %')
                    ->badge()
                    ->color(fn ($state) => (float) $state >= 100 ? 'success' : 'primary'),
                TextColumn::make('budget_hours')
                    ->label('Heures')
                    ->description(fn (Chantier $record) => 'Réel : '.$record->real_hours.'h')
                    ->color(fn (Chantier $record) => $record->real_hours > $record->budget_hours ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ChantierStatus::class),
                SelectFilter::make('manager_id')->label('Conducteur')->relationship('manager', 'last_name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print_os')
                        ->label('Ordre de Service')
                        ->icon(Phosphor::FilePdf)
                        ->color('info')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => $service->download($service->generateStartOrder($record))),
                    Action::make('print_bilan')
                        ->label('Bilan Analytique')
                        ->icon(Phosphor::ChartPie)
                        ->color('success')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => $service->download($service->generateRentabilityReport($record))),
                    Action::make('print_ppsps')
                        ->label('PPSPS (Sécurité)')
                        ->icon(Phosphor::HardHat)
                        ->color('danger')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => $service->download($service->generatePpsps($record))),

                    Action::make('affect_material')
                        ->label('Affecter Matériel')
                        ->icon(Phosphor::Package)
                        ->color('warning')
                        ->schema([
                            Select::make('warehouse_id')
                                ->label('Dépôt Source')
                                ->options(Warehouse::pluck('name', 'id'))
                                ->required()
                                ->reactive(),
                            Select::make('item_id')
                                ->label('Article')
                                ->options(function (callable $get) {
                                    $warehouseId = $get('warehouse_id');
                                    if (! $warehouseId) {
                                        return [];
                                    }

                                    return Item::whereHas('stocks', function ($q) use ($warehouseId) {
                                        $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0);
                                    })->pluck('name', 'id');
                                })
                                ->required(),
                            TextInput::make('quantity')->label('Quantité')
                                ->label('Quantité')
                                ->numeric()
                                ->required()
                                ->minValue(0.01),
                        ])
                        ->action(function (Chantier $record, array $data) {
                            $stock = Stock::where('warehouse_id', $data['warehouse_id'])
                                ->where('item_id', $data['item_id'])
                                ->firstOrFail();

                            $stock->decrease(
                                $data['quantity'],
                                "Affectation au chantier {$record->reference}",
                                StockMouvementSource::SITE,
                                $record->id
                            );

                            Notification::make()
                                ->title('Matériel affecté')
                                ->success()
                                ->send();
                        }),

                    Action::make('generate_invoice')
                        ->label('Facturer Situation')
                        ->icon(Phosphor::Receipt)
                        ->color('success')
                        ->visible(fn (Chantier $record) => $record->quote_id !== null)
                        ->schema([
                            TextInput::make('percentage')
                                ->label('Pourcentage d\'avancement à facturer')
                                ->numeric()
                                ->default(fn (Chantier $record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'])
                                ->minValue(1)
                                ->maxValue(100)
                                ->required()
                                ->suffix('%'),
                        ])
                        ->action(function (Chantier $record, array $data) {
                            $quote = $record->quote;
                            if (! $quote) {
                                return;
                            }

                            $percentage = $data['percentage'] / 100;
                            $amountHt = $quote->total_ht * $percentage;
                            $amountTva = $quote->total_tva * $percentage;

                            $invoice = CustomerInvoice::create([
                                'client_id' => $record->client_id,
                                'chantier_id' => $record->id,
                                'reference' => 'FACT-SIT-'.uniqid(),
                                'type' => InvoiceType::STANDARD,
                                'status' => InvoiceStatus::DRAFT,
                                'total_ht' => $amountHt,
                                'total_tva' => $amountTva,
                                'total_ttc' => $amountHt + $amountTva,
                                'due_date' => now()->addDays(30),
                                'responsable_id' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Facture de situation créée (Brouillon)')
                                ->success()
                                ->send();
                        }),

                    Action::make('affect_vehicle')
                        ->label('Assigner Véhicule')
                        ->icon(Phosphor::Truck)
                        ->color('info')
                        ->schema([
                            Select::make('vehicle_id')
                                ->label('Véhicule / Engin')
                                ->options(Vehicle::all()->mapWithKeys(fn ($v) => [$v->id => "{$v->brand} {$v->model} ({$v->license_plate})"]))
                                ->required()
                                ->searchable(),
                            Select::make('employee_id')
                                ->label('Conducteur (Optionnel)')
                                ->options(Employee::all()->mapWithKeys(fn ($e) => [$e->id => "{$e->first_name} {$e->last_name}"]))
                                ->searchable(),
                            DatePicker::make('started_at')
                                ->label('Date de début')
                                ->default(now())
                                ->required(),
                            DatePicker::make('ended_at')
                                ->label('Date de fin (Prévue)'),
                        ])
                        ->action(function (Chantier $record, array $data) {
                            VehicleAssignment::create([
                                'vehicle_id' => $data['vehicle_id'],
                                'chantier_id' => $record->id,
                                'employee_id' => $data['employee_id'] ?? null,
                                'started_at' => $data['started_at'],
                                'ended_at' => $data['ended_at'] ?? null,
                                'status' => AssignmentStatus::ACTIVE,
                                'purpose' => 'Affectation Chantier '.$record->reference,
                                'start_odometer' => Vehicle::find($data['vehicle_id'])->odometer ?? 0,
                            ]);

                            Notification::make()
                                ->title('Véhicule assigné au chantier')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
