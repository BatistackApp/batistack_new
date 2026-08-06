<?php

namespace App\Filament\Chantier\Resources\ChecklistTemplates\Schemas;

use Filament\Schemas\Schema;

class ChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nom du modèle')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Description / Instructions')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Builder::make('schema')
                    ->label('Questions de la Checklist')
                    ->columnSpanFull()
                    ->blocks([
                        \Filament\Forms\Components\Builder\Block::make('text_input')
                            ->label('Question Texte')
                            ->icon('heroicon-m-bars-3-bottom-left')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label('Libellé de la question')
                                    ->required(),
                                \Filament\Forms\Components\Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                        \Filament\Forms\Components\Builder\Block::make('checkbox')
                            ->label('Case à cocher (Oui/Non)')
                            ->icon('heroicon-m-check-circle')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label('Libellé de la question')
                                    ->required(),
                                \Filament\Forms\Components\Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                        \Filament\Forms\Components\Builder\Block::make('file_upload')
                            ->label('Prise de photo')
                            ->icon('heroicon-m-camera')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label('Instruction pour la photo')
                                    ->required(),
                                \Filament\Forms\Components\Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }
}
