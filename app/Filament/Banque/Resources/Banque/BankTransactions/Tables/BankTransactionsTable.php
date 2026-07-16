<?php

namespace App\Filament\Banque\Resources\Banque\BankTransactions\Tables;

use App\Enums\Banque\TransactionStatus;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Services\Banque\ReconciliationService;
use App\Services\Banque\StatementImportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class BankTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bankAccount.name')
                    ->label('Compte bancaire')
                    ->searchable(),
                TextColumn::make('external_id')
                    ->label('ID Externe')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('eur')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Banque\TransactionStatus::class),
                \Filament\Tables\Filters\Filter::make('date')
                    ->label('Plage de date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')->label('Du'),
                        \Filament\Forms\Components\DatePicker::make('created_until')->label('Au'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('lettrer')
                    ->label('Lettrer')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn (BankTransaction $record) => $record->status === TransactionStatus::PENDING)
                    ->schema(function (BankTransaction $record) {
                        $service = new ReconciliationService;
                        $suggestions = $service->suggestMatches($record);
                        $options = [];
                        foreach ($suggestions as $s) {
                            $ref = $s['model']->reference ?? ('Note de frais ' . $s['model']->id);
                            $options[$s['type'].':'.$s['model']->id] = "{$ref} (Score: {$s['score']}%)";
                        }

                        return [
                            Select::make('invoice_id')
                                ->label('Document correspondant')
                                ->options($options)
                                ->required()
                                ->searchable(),
                        ];
                    })
                    ->action(function (array $data, BankTransaction $record) {
                        [$type, $id] = explode(':', $data['invoice_id']);

                        $allowedTypes = [
                            CustomerInvoice::class,
                            SupplierInvoice::class,
                            \App\Models\RH\ExpenseReport::class,
                        ];

                        if (!in_array($type, $allowedTypes)) {
                            Notification::make()->title('Type de document invalide !')->danger()->send();
                            return;
                        }

                        $invoice = $type::find($id);

                        if ($invoice) {
                            $totalTtc = $invoice->total_ttc ?? $invoice->amount_ttc ?? $invoice->total_amount ?? 0;
                            $paidAmount = $invoice->morphMany(\App\Models\Banque\BankReconciliation::class, 'reconcilable')->sum('amount_applied');
                            $invoiceRemaining = $totalTtc - $paidAmount;
                            
                            if (abs($record->amount) > $invoiceRemaining + 0.05) {
                                Notification::make()
                                    ->title('Opération refusée')
                                    ->body('Le montant de la transaction (' . number_format(abs($record->amount), 2) . ' €) dépasse le solde restant (' . number_format($invoiceRemaining, 2) . ' €). Veuillez scinder la transaction ou corriger le montant.')
                                    ->danger()
                                    ->send();
                                
                                return; // Block the action
                            }
                            BankReconciliation::create([
                                'bank_transaction_id' => $record->id,
                                'reconcilable_type' => $type,
                                'reconcilable_id' => $id,
                                'amount_applied' => abs($record->amount),
                            ]);

                            // Générer également le paiement et son allocation, seulement pour les factures (pas RH)
                            if ($invoice instanceof CustomerInvoice || $invoice instanceof SupplierInvoice) {
                                $paymentType = $invoice instanceof CustomerInvoice 
                                    ? \App\Enums\Commerce\PaymentType::IN 
                                    : \App\Enums\Commerce\PaymentType::OUT;
                                    
                                $thirdPartyId = $invoice instanceof CustomerInvoice 
                                    ? $invoice->client_id 
                                    : $invoice->supplier_id;

                                $payment = \App\Models\Commerce\Payment::create([
                                    'third_party_id' => $thirdPartyId,
                                    'reference' => 'PAY-' . uniqid(),
                                    'type' => $paymentType,
                                    'method' => \App\Enums\Commerce\PaymentMethod::BANK_TRANSFER,
                                    'status' => \App\Enums\Commerce\PaymentStatus::COMPLETED,
                                    'amount' => abs($record->amount),
                                    'payment_date' => $record->date,
                                    'notes' => 'Lettrage bancaire ' . $record->external_id,
                                ]);

                                $payment->allocations()->create([
                                    'allocated_amount' => abs($record->amount),
                                    'payable_id' => $invoice->id,
                                    'payable_type' => get_class($invoice),
                                ]);
                            }

                            $record->update(['status' => TransactionStatus::RECONCILED]);
                            Notification::make()->title('Lettrage effectué avec succès')->success()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('auto_reconcile')
                        ->label('Lettrage Automatique')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->schema([
                            TextInput::make('threshold')
                                ->label('Seuil de confiance minimum (%)')
                                ->numeric()
                                ->default(80)
                                ->required()
                                ->minValue(1)
                                ->maxValue(100),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $service = new ReconciliationService;
                            $successCount = $service->bulkReconcile($records, (int) $data['threshold']);
                            Notification::make()
                                ->success()
                                ->title("{$successCount} transactions lettrées avec succès.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Importer un relevé')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->schema([
                        Select::make('bank_account_id')
                            ->label('Compte bancaire')
                            ->options(BankAccount::pluck('name', 'id'))
                            ->required(),
                        FileUpload::make('file')
                            ->label('Fichier (CSV)')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($data['file']);
                        if (file_exists($path)) {
                            $service = new StatementImportService;
                            $account = BankAccount::find($data['bank_account_id']);
                            $imported = $service->importCsv($account, $path);

                            Notification::make()
                                ->title('Import réussi')
                                ->body("{$imported} transactions importées.")
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }
}
