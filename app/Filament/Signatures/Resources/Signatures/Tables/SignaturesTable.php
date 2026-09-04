<?php

namespace App\Filament\Signatures\Resources\Signatures\Tables;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Mail\Core\MultiSignatureRequestedMail;
use App\Models\Core\Signature;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SignaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('signable_type')
                    ->label('Document')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signable_id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('signers_count')
                    ->label('Signataires')
                    ->state(function (Signature $record) {
                        $total = $record->total_signers;
                        $signed = $record->signed_count;

                        return "{$signed}/{$total}";
                    })
                    ->color(function (Signature $record) {
                        if ($record->signed_count === 0) {
                            return 'warning';
                        }

                        return $record->signed_count === $record->total_signers ? 'success' : 'info';
                    }),

                TextColumn::make('user.name')
                    ->label('Créé par')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(SignatureStatus::class),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(SignatureType::class),
                SelectFilter::make('is_multi_signatory')
                    ->label('Multi-signataires')
                    ->options([
                        '1' => 'Oui',
                        '0' => 'Non',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === '1') {
                            return $query->has('signers');
                        }

                        if ($state['value'] === '0') {
                            return $query->doesntHave('signers');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('resend')
                        ->label('Relancer')
                        ->icon(Phosphor::ArrowClockwise)
                        ->color('warning')
                        ->visible(fn (Signature $record) => $record->status === SignatureStatus::PENDING)
                        ->requiresConfirmation()
                        ->modalHeading('Relancer les demandes')
                        ->modalDescription('Les emails de demande seront renvoyés à tous les signataires en attente.')
                        ->action(function (Signature $record) {
                            $pendingSigners = $record->signers()
                                ->where('status', SignatureStatus::PENDING)
                                ->get();

                            foreach ($pendingSigners as $signer) {
                                Mail::to($signer->email)->send(new MultiSignatureRequestedMail($signer));
                            }

                            Notification::make()
                                ->title('Demandes relancées')
                                ->body(count($pendingSigners).' email(s) renvoyé(s).')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancel')
                        ->label('Annuler')
                        ->icon(Phosphor::XCircle)
                        ->color('danger')
                        ->visible(fn (Signature $record) => $record->status === SignatureStatus::PENDING)
                        ->requiresConfirmation()
                        ->modalHeading('Annuler la signature')
                        ->modalDescription('La demande de signature sera annulée. Cette action est irréversible.')
                        ->action(function (Signature $record) {
                            $record->update(['status' => SignatureStatus::EXPIRED]);
                            $record->signers()
                                ->where('status', SignatureStatus::PENDING)
                                ->update(['status' => SignatureStatus::EXPIRED]);

                            Notification::make()
                                ->title('Signature annulée')
                                ->send();
                        }),
                ]),
            ]);
    }
}
