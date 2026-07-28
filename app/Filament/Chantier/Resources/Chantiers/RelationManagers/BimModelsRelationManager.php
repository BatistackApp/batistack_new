<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Models\Vision3D\BimModel;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\FileUpload;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BimModelsRelationManager extends RelationManager
{
    protected static string $relationship = 'bimModels';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Maquettes 3D & Plans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                    
                Select::make('format')
                    ->label('Format')
                    ->options([
                        'ifc' => 'IFC (BIM)',
                        'dxf' => 'DXF (AutoCAD 2D/3D)',
                        'gltf' => 'glTF',
                        'glb' => 'GLB',
                        'obj' => 'OBJ',
                        'stl' => 'STL',
                    ])
                    ->required(),
                    
                FileUpload::make('file_path')
                    ->label('Fichier 3D')
                    ->disk('public')
                    ->directory('bim_models')
                    ->preserveFilenames()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                        return (string) str($file->getClientOriginalName())->prepend(now()->timestamp . '_');
                    })
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom'),
                Tables\Columns\BadgeColumn::make('format')->label('Format')
                    ->colors([
                        'primary' => 'ifc',
                        'warning' => 'dxf',
                        'success' => fn ($state) => in_array($state, ['glb', 'gltf']),
                        'secondary' => fn ($state) => in_array($state, ['obj', 'stl']),
                    ]),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn ($state) => number_format($state / 1048576, 2) . ' Mo'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Visualiser')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BimModel $record): string => \App\Filament\Resources\Vision3D\BimModelResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
