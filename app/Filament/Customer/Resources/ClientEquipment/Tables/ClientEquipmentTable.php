<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Models\Interventions\Intervention;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
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
            ->actions([
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
                        $contact = auth()->user()->contact;
                        
                        Intervention::create([
                            'company_id' => $record->company_id,
                            'third_party_id' => $contact->third_party_id,
                            'client_equipment_id' => $record->id,
                            'type' => InterventionType::REGIE,
                            'status' => InterventionStatus::SOUMIS,
                            'description' => $data['description'],
                            // 'reference' => ... ? Can be left to observer/default
                        ]);
                        
                        Notification::make()
                            ->title('Panne signalée avec succès')
                            ->body('Notre équipe va prendre en charge votre demande très rapidement.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Read-only
            ]);
    }
}
