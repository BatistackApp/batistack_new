<?php

namespace App\Filament\Chantier\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nom')
                    ->label('Nom du modèle')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
                Textarea::make('description')->label('Description')
                    ->label('Description / Instructions')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Builder::make('schema')
                    ->label('Questions de la Checklist')
                    ->columnSpanFull()
                    ->blocks([
                        Block::make('text_input')
                            ->label('Question Texte')
                            ->icon('heroicon-m-bars-3-bottom-left')
                            ->schema([
                                TextInput::make('name')->label('Nom')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                TextInput::make('label')
                                    ->label('Libellé de la question')
                                    ->required(),
                                Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                        Block::make('checkbox')
                            ->label('Case à cocher (Oui/Non)')
                            ->icon('heroicon-m-check-circle')
                            ->schema([
                                TextInput::make('name')->label('Nom')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                TextInput::make('label')
                                    ->label('Libellé de la question')
                                    ->required(),
                                Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                        Block::make('file_upload')
                            ->label('Prise de photo')
                            ->icon('heroicon-m-camera')
                            ->schema([
                                TextInput::make('name')->label('Nom')
                                    ->label('Identifiant technique (sans espace)')
                                    ->required(),
                                TextInput::make('label')
                                    ->label('Instruction pour la photo')
                                    ->required(),
                                Toggle::make('required')
                                    ->label('Obligatoire')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }
}
