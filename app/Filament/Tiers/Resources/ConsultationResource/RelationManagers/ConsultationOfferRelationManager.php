<?php

namespace App\Filament\Tiers\Resources\ConsultationResource\RelationManagers;

use App\Models\Tiers\ConsultationOffer;
use App\Notifications\Subcontractor\OfferAcceptedNotification;
use App\Notifications\Subcontractor\OfferRejectedNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ConsultationOfferRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Offres reçues';

    protected static ?string $modelLabel = 'Offre';

    protected static ?string $pluralModelLabel = 'Offres';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('thirdParty.name')
            ->columns([
                Tables\Columns\TextColumn::make('thirdParty.name')
                    ->label('Sous-traitant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant (€ HT)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn (ConsultationOffer $record): string => $record->message ?? ''),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soumise le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->checkIfRecordIsSelectable(fn (ConsultationOffer $record): bool => $record->status === 'submitted')
            ->recordActions([
                Action::make('accept')
                    ->label('Accepter')
                    ->icon(Phosphor::CheckCircle)
                    ->color('success')
                    ->visible(fn (ConsultationOffer $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->modalHeading('Accepter cette offre')
                    ->modalDescription('Êtes-vous sûr de vouloir accepter cette offre ? Les autres offres seront rejetées.')
                    ->action(function (ConsultationOffer $record) {
                        $notifications = [];

                        DB::transaction(function () use ($record, &$notifications) {
                            $consultation = $record->consultation()->lockForUpdate()->first();

                            if ($consultation->status !== 'closed') {
                                Notification::make()
                                    ->title('Impossible d\'accepter')
                                    ->body('Cette consultation n\'est plus ouverte aux réponses.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $freshRecord = $record->fresh();

                            if ($freshRecord->status !== 'submitted') {
                                Notification::make()
                                    ->title('Impossible d\'accepter')
                                    ->body('Cette offre n\'est plus en attente.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($consultation->offers()->where('status', 'accepted')->exists()) {
                                Notification::make()
                                    ->title('Impossible d\'accepter')
                                    ->body('Une offre a déjà été acceptée pour cette consultation.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $freshRecord->update(['status' => 'accepted']);

                            $notifications[] = fn () => $this->notifySubcontractor($freshRecord, OfferAcceptedNotification::class);

                            $consultation->offers()
                                ->where('id', '!=', $freshRecord->id)
                                ->where('status', 'submitted')
                                ->each(function (ConsultationOffer $rejected) use (&$notifications) {
                                    $rejected->update(['status' => 'rejected']);
                                    $notifications[] = fn () => $this->notifySubcontractor($rejected, OfferRejectedNotification::class);
                                });

                            $consultation->update(['status' => 'awarded']);
                        });

                        foreach ($notifications as $dispatch) {
                            $dispatch();
                        }

                        Notification::make()
                            ->title('Offre acceptée')
                            ->body("L'offre de {$record->thirdParty->name} a été acceptée. Le sous-traitant a été notifié par email.")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon(Phosphor::XCircle)
                    ->color('danger')
                    ->visible(fn (ConsultationOffer $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter cette offre')
                    ->action(function (ConsultationOffer $record) {
                        $notification = null;

                        DB::transaction(function () use ($record, &$notification) {
                            $consultation = $record->consultation()->lockForUpdate()->first();

                            $freshRecord = $record->fresh();

                            if ($freshRecord->status !== 'submitted') {
                                Notification::make()
                                    ->title('Impossible de rejeter')
                                    ->body('Cette offre n\'est plus en attente.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $freshRecord->update(['status' => 'rejected']);

                            $notification = fn () => $this->notifySubcontractor($freshRecord, OfferRejectedNotification::class);
                        });

                        if ($notification) {
                            $notification();
                        }

                        Notification::make()
                            ->title('Offre rejetée')
                            ->body("L'offre de {$record->thirdParty->name} a été rejetée. Le sous-traitant a été notifié par email.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function () {
                            $selectedRecords = $this->getSelectedRecords();
                            $nonDeletable = $selectedRecords->reject(fn (ConsultationOffer $offer) => $offer->status === 'submitted');

                            if ($nonDeletable->isNotEmpty()) {
                                Notification::make()
                                    ->title('Suppression refusée')
                                    ->body('Seules les offres en attente ("submitted") peuvent être supprimées.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $selectedRecords->each->delete();
                        }),
                ]),
            ]);
    }

    protected function notifySubcontractor(ConsultationOffer $offer, string $notificationClass): void
    {
        $offer->thirdParty->contacts()
            ->whereNotNull('user_id')
            ->each(fn ($contact) => $contact->user->notify(new $notificationClass($offer)));
    }
}
