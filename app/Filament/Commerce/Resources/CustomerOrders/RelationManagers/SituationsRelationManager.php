<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Commerce\CustomerSituation;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\CustomerOrderService;
use App\Services\Commerce\InvoiceLegalizationService;
use App\Services\Commerce\SituationService;
use CodeWithKyrian\FilamentDateRange\Forms\Components\DateRangePicker;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use function Pest\Laravel\options;

class SituationsRelationManager extends RelationManager
{
    protected static string $relationship = 'situations';

    protected static ?string $title = 'Situation de travaux';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('Numéro de situation')
                    ->disabled(),

                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->disabled(),

                TextInput::make('total_ht')
                    ->label('Total HT')
                    ->disabled()
                    ->prefix('€'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')
                    ->label('Situation N°')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('retenue_garantie_amount')
                    ->label('Retenue 5%')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('prorata_amount')
                    ->label('Prorata')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('created_at')->label('Créé le')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(InvoiceStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nouvelle situation')
                    ->schema([
                        Repeater::make('progress_data')
                            ->label('Avancement par section')
                            ->columns(2)
                            ->schema([
                                Select::make('item_id')
                                    ->label('Section de travaux')
                                    ->options(fn (RelationManager $livewire) => CustomerOrderItem::with('item')
                                        ->where('customer_order_id', $livewire->getOwnerRecord()->id)
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('percentage')
                                    ->label('% d\'avancement')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('periode_start')
                                    ->label('Date de début de la situation'),

                                DatePicker::make('periode_end')
                                    ->label('Date de fin de la situation'),
                            ])
                            ->columnSpanFull(),

                        TextInput::make('retention_rate')
                            ->label('Retenue de garantie (%)')
                            ->numeric()
                            ->default(5.0)
                            ->required()
                            ->prefix('%'),

                        TextInput::make('prorata_rate')
                            ->label('Compte prorata (%)')
                            ->numeric()
                            ->default(0.0)
                            ->prefix('%'),
                    ])
                    ->using(function (array $data, RelationManager $livewire, SituationService $service, InvoiceLegalizationService $legalService) {
                        $order = $livewire->getOwnerRecord();
                        $progressData = [];

                        foreach ($data['progress_data'] ?? [] as $progress) {
                            // La clé est l'ID de l'item, la valeur est le pourcentage
                            $progressData[$progress['item_id']] = $progress['percentage'];
                        }

                        $situation = $service->generateNextSituation(
                            order: $order,
                            responsable: Auth::user(),
                            progressData: $progressData,
                            startPeriod: $data['periode_start'],
                            endPeriod: $data['periode_end'],
                            retenueGarantieRate: $data['retention_rate'],
                            prorataRate: $data['prorata_rate'],
                        );

                        $invoice = CustomerInvoice::create([
                            'client_id' => $order->client_id,
                            'chantier_id' => $order->chantier_id,
                            'customer_order_id' => $order->id,
                            'customer_situation_id' => $situation->id,
                            'type' => InvoiceType::SITUATION,
                            'status' => InvoiceStatus::DRAFT,
                            'total_ht' => $situation->total_ht,
                            'total_ttc' => $situation->total_ttc,
                            'due_date' => now()->addDays(30),
                            'responsable_id' => \auth()->user()->id,
                            'reference' => app(CustomerOrderService::class)->generateReferenceInvoice(),
                        ]);

                        $legalService->legalizeCustomerInvoice($invoice);

                        Notification::make()
                            ->title('Situation Créée')
                            ->body("Situation n°{$situation->number} créée et facturée automatiquement")
                            ->success()
                            ->send();

                        return $situation;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('printpdf')
                        ->label('Imprimer')
                        ->icon(Phosphor::Printer)
                        ->action(fn (CustomerSituation $record, CommerceDocumentationService $service) => $service->download($service->generateSituationPdf($record))),

                    Action::make('viewRelatedInvoice')
                        ->label('Voir Facture')
                        ->icon(Phosphor::File)
                        ->visible(fn (CustomerSituation $record) => $record->invoice !== null)
                        ->modalHeading(fn (CustomerSituation $record) => 'Facture n°'.$record->invoice->reference)
                        ->schema(fn (CustomerSituation $record) => [
                            Section::make()
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('reference')->label('Référence')
                                        ->label('Référence'),
                                ]),
                        ]),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
