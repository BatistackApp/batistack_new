<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Tables;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClientEquipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('brand')->label('Marque')->searchable(),
                TextColumn::make('serial_number')->label('N° de série')->searchable(),
                TextColumn::make('installation_date')->label('Date d\'installation')->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('report_breakdown')
                    ->label('Signaler une panne')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        Textarea::make('description')
                            ->label('Description du problème')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data, Model $record) {
                        $contact = auth()->user()?->contact;

                        if (! $contact) {
                            Notification::make()
                                ->title('Erreur')
                                ->body('Aucun contact associé à votre compte.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Intervention::create([
                            'company_id' => $record->company_id,
                            'third_party_id' => $contact->third_party_id,
                            'client_equipment_id' => $record->id,
                            'type' => InterventionType::REGIE,
                            'status' => InterventionStatus::SOUMIS,
                            'description' => $data['description'],
                        ]);

                        Notification::make()
                            ->title('Panne signalée avec succès')
                            ->body('Notre équipe va prendre en charge votre demande très rapidement.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                // Read-only
            ]);
    }
}
