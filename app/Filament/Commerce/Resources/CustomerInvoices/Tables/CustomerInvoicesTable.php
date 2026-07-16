<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Services\Commerce\CommerceDocumentationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
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

                IconColumn::make('is_overdue')
                    ->label('En retard')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),

                SelectFilter::make('type')
                    ->options(InvoiceType::class),

                SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_overdue')
                    ->label('Factures en retard')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now())->where('status', '!=', InvoiceStatus::PAID)),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print')
                        ->label('PDF')
                        ->icon('heroicon-o-document')
                        ->action(fn (CustomerInvoice $record, CommerceDocumentationService $service) => response()->download($service->generateInvoicePdf($record))),

                    Action::make('sendReminder')
                        ->label('Relancer')
                        ->icon('heroicon-o-bell-alert')
                        ->visible(fn (CustomerInvoice $record) => $record->status === InvoiceStatus::VALIDATED && $record->due_date < now())
                        ->requiresConfirmation()
                        ->action(fn (CustomerInvoice $record) => Notification::make()
                            ->title('Relance envoyée')
                            ->success()
                            ->send()),

                    Action::make('legalize')
                        ->label('Valider définitivement')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->visible(fn (CustomerInvoice $record) => $record->status === InvoiceStatus::DRAFT)
                        ->requiresConfirmation()
                        ->modalHeading('Valider définitivement cette facture')
                        ->modalDescription('Attention, cette action est irréversible ! La facture va recevoir un numéro définitif et sera scellée. Toute modification ultérieure nécessitera la création d\'un Avoir.')
                        ->modalSubmitActionLabel('Oui, sceller la facture')
                        ->action(function (CustomerInvoice $record) {
                            try {
                                app(\App\Services\Commerce\InvoiceLegalizationService::class)->legalizeCustomerInvoice($record);
                                Notification::make()->success()->title('Facture scellée avec succès !')->send();
                            } catch (\Exception $e) {
                                Notification::make()->danger()->title('Erreur de validation')->body($e->getMessage())->send();
                            }
                        }),

                    Action::make('generateCreditNote')
                        ->label('Créer un Avoir')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn (CustomerInvoice $record) => in_array($record->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAID]))
                        ->form([
                            \Filament\Forms\Components\TextInput::make('amount_ht')
                                ->label('Montant HT de l\'Avoir')
                                ->numeric()
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(function (array $data, CustomerInvoice $record) {
                            try {
                                $creditNote = app(\App\Services\Commerce\CustomerOrderService::class)->createCreditNote(
                                    $record, 
                                    $data['amount_ht'], 
                                    $data['reason'], 
                                    auth()->user()
                                );
                                Notification::make()->success()->title('Avoir généré')->send();
                            } catch (\Exception $e) {
                                Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                            }
                        }),
                ]),
            ]);
    }
}
