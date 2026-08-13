<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable(),
                TextColumn::make('thirdParty.name')->label('Tiers')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('error_message')->label('Erreur')->limit(30),
                TextColumn::make('sent_at')->label('Date d\'envoi')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter manuel'),
                \Filament\Actions\Action::make('generateRecipients')
                    ->label('Générer la cible')
                    ->icon('heroicon-o-users')
                    ->form([
                        \Filament\Forms\Components\Select::make('third_party_types')
                            ->label('Types de tiers')
                            ->multiple()
                            ->options(\App\Enums\Tiers\ThirdPartyType::class)
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $campaignId = $livewire->getOwnerRecord()->id;
                        $types = $data['third_party_types'];
                
                        $contacts = \App\Models\Tiers\Contact::with('thirdParty')
                            ->whereHas('thirdParty', function($q) use ($types) {
                                $q->whereIn('type', $types);
                            })
                            ->whereNotNull('email')
                            ->where('email', '!=', '')
                            ->get();
                
                        foreach($contacts as $contact) {
                            \App\Models\Tiers\EmailCampaignRecipient::firstOrCreate([
                                'email_campaign_id' => $campaignId,
                                'email' => $contact->email,
                            ], [
                                'third_party_id' => $contact->third_party_id,
                                'contact_id' => $contact->id,
                                'status' => \App\Enums\Tiers\EmailCampaignRecipientStatus::PENDING,
                            ]);
                        }
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
