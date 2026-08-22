<?php

namespace App\Filament\Vision3D\Resources;

use App\Filament\Vision3D\Resources\BimModelResource\Pages;
use App\Models\Vision3D\BimModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BimModelResource extends Resource
{
    protected static ?string $model = BimModel::class;

    protected static ?string $modelLabel = 'Maquette 3D';
    protected static ?string $pluralModelLabel = 'Maquettes 3D';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Vision 3D';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails de la maquette')
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
                                return (string) str($file->getClientOriginalName())->prepend(now()->timestamp . '_');
                            })
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Aperçu')
                    ->state(fn ($record) => $record->thumbnail_path
                        ? Storage::disk('public')->url($record->thumbnail_path)
                        : null)
                    ->circular()
                    ->stacked()
                    ->limit(1),

                Tables\Columns\TextColumn::make('name')->label('Nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('format')
                    ->label('Format')
                    ->colors([
                        'primary' => 'ifc',
                        'warning' => 'dxf',
                        'success' => fn ($state) => in_array($state, ['glb', 'gltf']),
                        'secondary' => fn ($state) => in_array($state, ['obj', 'stl']),
                    ]),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn ($state) => number_format($state / 1048576, 2) . ' Mo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Visualisation 3D')
                    ->components([
                        ViewEntry::make('viewer')
                            ->view('filament.components.bim-viewer')
                            ->statePath('file_path') // Pass the file path state
                            ->registerActions([
                                Action::make('createAnnotation')
                                    ->form([
                                        TextInput::make('title')
                                            ->label('Titre')
                                            ->required(),
                                        Textarea::make('description')->label('Description')
                                            ->label('Description'),
                                        \Filament\Forms\Components\MorphToSelect::make('target')
                                            ->label('Lier à un élément')
                                            ->types([
                                                \Filament\Forms\Components\MorphToSelect\Type::make(\App\Models\Chantiers\ChantierTask::class)
                                                    ->label('Tâche de chantier')
                                                    ->titleAttribute('title'),
                                                \Filament\Forms\Components\MorphToSelect\Type::make(\App\Models\Interventions\Intervention::class)
                                                    ->label('Intervention')
                                                    ->titleAttribute('title'),
                                            ])
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->action(function (array $arguments, array $data, BimModel $record) {
                                        $record->annotations()->create([
                                            'title' => $data['title'],
                                            'description' => $data['description'],
                                            'position_x' => $arguments['x'],
                                            'position_y' => $arguments['y'],
                                            'position_z' => $arguments['z'],
                                            'target_type' => $data['target_type'] ?? null,
                                            'target_id' => $data['target_id'] ?? null,
                                        ]);
                                    }),
                                Action::make('viewAnnotation')
                                    ->modalHeading('Détails de l\'annotation')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Fermer')
                                    ->infolist(function (array $arguments) {
                                        $annotation = \App\Models\Vision3D\BimAnnotation::with('target')->find($arguments['id'] ?? null);
                                        if (!$annotation) return [];
                                        
                                        $components = [
                                            TextEntry::make('title')->label('Titre')->default($annotation->title),
                                            TextEntry::make('description')->label('Description')->default($annotation->description),
                                        ];

                                        if ($annotation->target) {
                                            $components[] = Section::make('Élément lié')
                                                ->schema([
                                                    TextEntry::make('target.title')->label('Titre')->default($annotation->target->title),
                                                    TextEntry::make('target.status')->label('Statut')->default($annotation->target->status?->getLabel() ?? 'En cours'),
                                                ]);
                                        }

                                        return $components;
                                    }),
                                Action::make('saveClashes')
                                    ->action(function (array $arguments, BimModel $record) {
                                        $clashes = $arguments['clashes'] ?? [];
                                        foreach ($clashes as $clash) {
                                            $record->annotations()->create([
                                                'title' => 'Collision détectée',
                                                'description' => 'Collision automatique entre ' . $clash['layer1'] . ' et ' . $clash['layer2'],
                                                'position_x' => $clash['x'],
                                                'position_y' => $clash['y'],
                                                'position_z' => $clash['z'],
                                            ]);
                                        }
                                        \Filament\Notifications\Notification::make()
                                            ->title(count($clashes) . ' collisions sauvegardées avec succès.')
                                            ->success()
                                            ->send();
                                    })
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Informations')
                    ->components([
                        TextEntry::make('name')->label('Nom'),
                        TextEntry::make('format')->label('Format'),
                        TextEntry::make('file_size')
                            ->label('Taille')
                            ->formatStateUsing(fn ($state) => number_format($state / 1048576, 2) . ' Mo'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BimAnnotationsRelationManager::class,
            RelationManagers\BimQuantitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBimModels::route('/'),
            'create' => Pages\CreateBimModel::route('/create'),
            'view' => Pages\ViewBimModel::route('/{record}'),
            'edit' => Pages\EditBimModel::route('/{record}/edit'),
        ];
    }
}
