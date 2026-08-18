<?php

namespace App\Filament\RH\Resources\TrainingSessions\RelationManagers;

use App\Enums\RH\TrainingParticipantStatus;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $recordTitleAttribute = 'last_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('status')
                    ->options(TrainingParticipantStatus::class)
                    ->required()
                    ->default(TrainingParticipantStatus::INSCRIT->value)
                    ->label('Statut du participant'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Prénom'),
                Tables\Columns\TextColumn::make('last_name')->label('Nom'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('status')
                            ->options(TrainingParticipantStatus::class)
                            ->default(TrainingParticipantStatus::INSCRIT->value)
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
