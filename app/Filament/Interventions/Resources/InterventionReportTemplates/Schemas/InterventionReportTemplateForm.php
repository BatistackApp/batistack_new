<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates\Schemas;

use App\Enums\Interventions\InterventionType;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InterventionReportTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom du modèle')
                    ->required()
                    ->maxLength(255),
                Select::make('intervention_type')
                    ->label('Type d\'intervention')
                    ->options(InterventionType::class)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Description / Instructions')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Builder::make('schema')
                    ->label('Questions du rapport')
                    ->columnSpanFull()
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->blocks([
                        Builder\Block::make('text_input')
                            ->label('Texte')
                            ->icon('heroicon-m-bars-3-bottom-left')
                            ->schema(self::baseFields()),
                        Builder\Block::make('textarea')
                            ->label('Texte long')
                            ->icon('heroicon-m-document-text')
                            ->schema(self::baseFields()),
                        Builder\Block::make('number')
                            ->label('Nombre')
                            ->icon('heroicon-m-hashtag')
                            ->schema([
                                ...self::baseFields(),
                                TextInput::make('min')
                                    ->label('Valeur minimum')
                                    ->numeric(),
                                TextInput::make('max')
                                    ->label('Valeur maximum')
                                    ->numeric(),
                            ]),
                        Builder\Block::make('checkbox')
                            ->label('Case à cocher (Oui/Non)')
                            ->icon('heroicon-m-check-circle')
                            ->schema(self::baseFields()),
                        Builder\Block::make('select')
                            ->label('Liste déroulante')
                            ->icon('heroicon-m-chevron-up-down')
                            ->schema([
                                ...self::baseFields(),
                                Textarea::make('options')
                                    ->label('Options (une par ligne)')
                                    ->rows(4)
                                    ->helperText('Chaque ligne correspond à une option proposée au technicien.'),
                            ]),
                        Builder\Block::make('date')
                            ->label('Date')
                            ->icon('heroicon-m-calendar-days')
                            ->schema(self::baseFields()),
                        Builder\Block::make('file_upload')
                            ->label('Photo / Fichier')
                            ->icon('heroicon-m-camera')
                            ->schema(self::baseFields()),
                    ]),
            ]);
    }

    private static function baseFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Identifiant technique (sans espace)')
                ->required()
                ->regex('/^[a-z_][a-z0-9_]*$/'),
            TextInput::make('label')
                ->label('Libellé de la question')
                ->required(),
            Toggle::make('required')
                ->label('Obligatoire')
                ->default(false),
        ];
    }
}