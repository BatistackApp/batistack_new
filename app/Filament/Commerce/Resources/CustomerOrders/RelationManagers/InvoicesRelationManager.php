<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\CustomerOrderService;
use App\Services\Commerce\InvoiceLegalizationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Factures';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->label('Numéro')
                    ->disabled(),

                Select::make('type')
                    ->label('Type')
                    ->options(InvoiceType::class)
                    ->required(),

                Select::make('status')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->disabled(),

                DatePicker::make('due_date')
                    ->label('Échéance')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),

                SelectFilter::make('type')
                    ->options(InvoiceType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nouvelle facture')
                    ->schema([
                        Select::make('type')
                            ->label('Type de facture')
                            ->options(InvoiceType::class)
                            ->required()
                            ->live(),

                        TextInput::make('acompte_amount')
                            ->label('Montant d\'acompte')
                            ->numeric()
                            ->prefix('€')
                            ->visible(fn (Get $get) => $get('type') === InvoiceType::ACOMPTE),

                        DatePicker::make('due_date')
                            ->label('Échéance')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->using(function (array $data, RelationManager $livewire) {
                        $order = $livewire->getOwnerRecord();
                        $orderService = app(CustomerOrderService::class);
                        $legalizationService = app(InvoiceLegalizationService::class);

                        // Création de la facture via le service
                        $invoice = $orderService->createInvoice(
                            $order,
                            InvoiceType::from($data['type']),
                            responsable: Auth::user()->id,
                            acompteAmount: $data['acompte_amount'] ?? null,
                        );

                        // Mise à jour de l'échéance
                        $invoice->update(['due_date' => $data['due_date']]);

                        // Légalisation automatique
                        $legalizationService->legalizeCustomerInvoice($invoice);

                        return $invoice;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('print')
                        ->label('Imprimer')
                        ->icon(Phosphor::Printer)
                        ->action(fn (Model $record, CommerceDocumentationService $service) => response()->download($service->generateInvoicePdf($record))),

                    Action::make('recordPayment')
                        ->label('Payer')
                        ->icon(Phosphor::Bank)
                        ->visible(fn (Model $record) => $record->status !== InvoiceStatus::PAID)
                        ->url(fn (Model $record) => route('filament.commerce.resources.payments.create', ['invoice' => $record->id])),

                    Action::make('sendReminder')
                        ->label('Relancer')
                        ->icon(Phosphor::BellRinging)
                        ->visible(fn (CustomerInvoice $record) => $record->status === InvoiceStatus::VALIDATED && $record->due_date < now())
                        ->action(fn (CustomerInvoice $record) => Notification::make()
                            ->title('Relance envoyée')
                            ->body("Email de relance envoyé à {$record->client->name}")
                            ->success()
                            ->send()),

                    Action::make('createCreditNote')
                        ->label('Créer avoir')
                        ->icon(Phosphor::ArrowsIn)
                        ->visible(fn (CustomerInvoice $record) => $record->status === InvoiceStatus::VALIDATED)
                        ->schema([
                            TextInput::make('amount')
                                ->label('Montant de l\'avoir')
                                ->numeric()
                                ->required()
                                ->prefix('€'),

                            Textarea::make('reason')
                                ->label('Motif')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function (array $data, CustomerInvoice $record) {
                            $orderService = app(CustomerOrderService::class);
                            $creditNote = $orderService->createCreditNote(
                                invoice: $record,
                                amountHt: $data['amount'],
                                reason: $data['reason'],
                                responsable: Auth::user()->id,
                            );

                            Notification::make()
                                ->title('Avoir crée')
                                ->body("Avoir n°{$creditNote->reference} créé avec succès")
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

    public function isReadOnly(): bool
    {
        return false;
    }
}
