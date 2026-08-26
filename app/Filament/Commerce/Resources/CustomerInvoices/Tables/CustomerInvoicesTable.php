<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Jobs\Commerce\SendCustomerInvoiceEmailJob;
use App\Jobs\Commerce\SendCustomerStatementEmailJob;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\CustomerOrderService;
use App\Services\Commerce\InvoiceLegalizationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                TextColumn::make('reference')->label('Référence')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')->label('Statut')
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
                SelectFilter::make('status')->label('Statut')
                    ->options(InvoiceStatus::class),

                SelectFilter::make('type')->label('Type')
                    ->options(InvoiceType::class),

                SelectFilter::make('client_id')->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_overdue')
                    ->label('Factures en retard')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now())->where('status', '!=', InvoiceStatus::PAID)),
            ])
            ->headerActions([
                Action::make('sendCustomerStatement')
                    ->label('Envoyer un relevé client')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Select::make('client_id')->label('Client')
                            ->label('Client')
                            ->options(fn () => ThirdParty::clients()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required(),
                        DatePicker::make('start_date')
                            ->label('Du')
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Au')
                            ->native(false),
                        Select::make('status')->label('Statut')
                            ->label('Statut des factures')
                            ->options(InvoiceStatus::class)
                            ->placeholder('Tous les statuts'),
                        TextInput::make('email')->label('Email')
                            ->label('Email destinataire')
                            ->email()
                            ->helperText('Laissez vide pour utiliser le contact principal du client.'),
                    ])
                    ->action(function (array $data) {
                        SendCustomerStatementEmailJob::dispatch(
                            (int) $data['client_id'],
                            $data['start_date'] ?? null,
                            $data['end_date'] ?? null,
                            $data['status'] ?? null,
                            $data['email'] ?? null,
                        );

                        Notification::make()
                            ->title('Relevé client en cours d’envoi')
                            ->body('Le PDF sera généré puis envoyé par email.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print')
                        ->label('PDF')
                        ->icon('heroicon-o-document')
                        ->action(fn (CustomerInvoice $record, CommerceDocumentationService $service) => $service->download($service->generateInvoicePdf($record))),

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
                                app(InvoiceLegalizationService::class)->legalizeCustomerInvoice($record);
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
                            TextInput::make('amount_ht')
                                ->label('Montant HT de l\'Avoir')
                                ->numeric()
                                ->required(),
                            Textarea::make('reason')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(function (array $data, CustomerInvoice $record) {
                            try {
                                $creditNote = app(CustomerOrderService::class)->createCreditNote(
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('sendByEmail')
                        ->label('Envoyer les factures par email')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->modalHeading('Envoyer les factures sélectionnées')
                        ->modalDescription('Les factures validées, partiellement payées ou payées seront envoyées au contact principal du client avec le PDF en pièce jointe.')
                        ->action(function ($records) {
                            $sent = 0;
                            $skipped = 0;

                            foreach ($records as $invoice) {
                                if (! in_array($invoice->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAID], true)) {
                                    $skipped++;

                                    continue;
                                }

                                SendCustomerInvoiceEmailJob::dispatch($invoice);
                                $sent++;
                            }

                            Notification::make()
                                ->title("{$sent} facture(s) mise(s) en file d’envoi")
                                ->body($skipped > 0 ? "{$skipped} facture(s) ignorée(s) car non envoyable(s)." : null)
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
