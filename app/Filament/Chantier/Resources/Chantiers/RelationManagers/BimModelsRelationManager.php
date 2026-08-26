<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Filament\Vision3D\Resources\BimModelResource;
use App\Models\Vision3D\BimModel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
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
                TextInput::make('name')->label('Nom')
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
                        return (string) str($file->getClientOriginalName())->prepend(now()->timestamp.'_');
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->description(fn (BimModel $record) => 'Version '.$record->version.($record->parent_id ? ' (Révision)' : '')),
                Tables\Columns\TextColumn::make('format')->label('Format')
                    ->badge()
                    ->colors([
                        'primary' => 'ifc',
                        'warning' => 'dxf',
                        'success' => fn ($state) => in_array($state, ['glb', 'gltf']),
                        'secondary' => fn ($state) => in_array($state, ['obj', 'stl']),
                    ]),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn ($state) => number_format($state / 1048576, 2).' Mo'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Visualiser')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BimModel $record): string => BimModelResource::getUrl('view', ['record' => $record], panel: 'vision3d')),
                Action::make('new_revision')
                    ->label('Nouvelle Révision')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        FileUpload::make('file_path')
                            ->label(fn (BimModel $record) => 'Nouveau Fichier IFC (V'.($record->version + 1).')')
                            ->disk('public')
                            ->directory('bim_models')
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str($file->getClientOriginalName())->prepend(now()->timestamp.'_');
                            })
                            ->required(),
                    ])
                    ->action(function (BimModel $record, array $data) {
                        $newModel = $record->replicate(['file_size', 'thumbnail_path']);
                        $newModel->file_path = $data['file_path'];
                        $newModel->parent_id = $record->id;
                        $newModel->version = $record->version + 1;

                        // Recalculate file size
                        if (\Storage::disk('public')->exists($data['file_path'])) {
                            $newModel->file_size = \Storage::disk('public')->size($data['file_path']);
                        }

                        $newModel->save();
                    })
                    ->visible(fn (BimModel $record) => $record->format === 'ifc'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
