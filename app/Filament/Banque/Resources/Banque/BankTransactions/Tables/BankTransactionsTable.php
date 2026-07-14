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
                            $options[$s['type'].':'.$s['model']->id] = "{$s['model']->reference} (Score: {$s['score']}%)";
                        }

                        return [
                            Select::make('invoice_id')
                                ->label('Facture correspondante')
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
                        ];

                        if (!in_array($type, $allowedTypes)) {
                            Notification::make()->title('Type de document invalide !')->danger()->send();
                            return;
                        }

                        $invoice = $type::find($id);

                        if ($invoice) {
                            $invoiceRemaining = $invoice->total_amount - $invoice->paid_amount;
                            if (abs($record->amount) > $invoiceRemaining) {
                                Notification::make()
                                    ->title('Le montant de la transaction dépasse le solde restant de la facture')
                                    ->danger()
                                    ->send();
                            }
                            BankReconciliation::create([
                                'bank_transaction_id' => $record->id,
                                'reconcilable_type' => $type,
                                'reconcilable_id' => $id,
                                'amount_applied' => abs($record->amount),
                            ]);
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
