<?php

namespace App\Filament\Vision3D\Resources\RelationManagers;

use App\Models\Chantiers\ChantierTask;
use App\Models\Interventions\Intervention;
use App\Models\Vision3D\BimAnnotation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Component;

class BimAnnotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'annotations';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Punaises & Annotations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')->label('Description')
                    ->label('Description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                MorphToSelect::make('target')
                    ->label('Lier à un élément')
                    ->types([
                        Type::make(ChantierTask::class)
                            ->label('Tâche de chantier')
                            ->titleAttribute('title'),
                        Type::make(Intervention::class)
                            ->label('Intervention')
                            ->titleAttribute('title'),
                    ])
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                // Les coordonnées XYZ sont gérées par le composant 3D
                TextInput::make('position_x')
                    ->numeric()
                    ->disabled(),
                TextInput::make('position_y')
                    ->numeric()
                    ->disabled(),
                TextInput::make('position_z')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titre'),
                Tables\Columns\TextColumn::make('description')->label('Description')
                    ->label('Description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Normalement les annotations sont créées via la vue 3D
            ])
            ->actions([
                Action::make('focus')
                    ->label('Voir dans la maquette')
                    ->icon('heroicon-o-eye')
                    ->action(function (BimAnnotation $record, Component $livewire) {
                        $livewire->dispatch('focus-annotation',
                            x: $record->position_x,
                            y: $record->position_y,
                            z: $record->position_z
                        );
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
