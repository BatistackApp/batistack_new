<?php

namespace App\Filament\Subcontractor\Resources;

use App\Filament\Subcontractor\Resources\ConsultationResource\Pages;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ConsultationOffer;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static string|\BackedEnum|null $navigationIcon = Phosphor::Handshake;

    protected static ?string $modelLabel = 'Appel d\'Offre / Consultation';

    protected static ?string $pluralModelLabel = 'Appels d\'Offres (Bourse)';

    public static function getEloquentQuery(): Builder
    {
        // Les sous-traitants ne voient que les consultations publiées (ou clôturées pour l'historique)
        return parent::getEloquentQuery()->whereIn('status', ['published', 'closed', 'awarded']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Date limite')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'closed' => 'warning',
                        'awarded' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('respond')
                    ->label('Répondre (Soumettre Offre)')
                    ->icon(Phosphor::PaperPlaneTilt)
                    ->color('primary')
                    ->visible(fn (Consultation $record) => $record->status === 'published')
                    ->schema([
                        TextInput::make('amount')->label('Montant')
                            ->label('Montant de votre offre (€ HT)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('message')
                            ->label('Message / Note')
                            ->maxLength(1000),
                    ])
                    ->action(function (Consultation $record, array $data) {
                        // Normally, the user has a linked ThirdParty ID. We assume auth()->user()->third_party_id exists or we take the first Subcontractor for demo.
                        $thirdPartyId = auth()->user()->contact->third_party_id ?? ThirdParty::subcontractors()->first()->id;

                        ConsultationOffer::updateOrCreate(
                            ['consultation_id' => $record->id, 'third_party_id' => $thirdPartyId],
                            ['amount' => $data['amount'], 'message' => $data['message'], 'status' => 'submitted']
                        );

                        Notification::make()
                            ->title('Offre soumise avec succès !')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultations::route('/'),
        ];
    }
}
