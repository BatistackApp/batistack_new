<?php

namespace App\Filament\RH\Resources\Interviews\Schemas;

use App\Enums\RH\InterviewStatus;
use App\Enums\RH\InterviewType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class InterviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Général')
                        ->schema([
                            Select::make('employee_id')
                                ->relationship('employee', 'id')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                                ->required()
                                ->label('Employé'),
                            Select::make('manager_id')
                                ->relationship('manager', 'name')
                                ->default(auth()->id())
                                ->required()
                                ->label('Manager'),
                            Select::make('type')
                                ->options(InterviewType::class)
                                ->required()
                                ->label('Type d\'entretien'),
                            Select::make('status')
                                ->options(InterviewStatus::class)
                                ->default(InterviewStatus::PLANIFIE->value)
                                ->required()
                                ->label('Statut'),
                            DateTimePicker::make('scheduled_at')
                                ->required()
                                ->format('d/m/Y H:i')
                                ->label('Date et heure prévue'),
                        ])->columns(2),
                    Wizard\Step::make('Évaluation')
                        ->schema([
                            Repeater::make('evaluation_grid')
                                ->label('Grille d\'évaluation dynamique')
                                ->schema([
                                    TextInput::make('question')->required()->label('Compétence / Objectif'),
                                    Textarea::make('answer')->label('Évaluation / Commentaire du manager'),
                                ])
                                ->collapsible()
                                ->defaultItems(3),
                        ]),
                    Wizard\Step::make('Signatures')
                        ->schema([
                            SignaturePad::make('employee_signature')
                                ->label('Signature du Collaborateur')
                                ->penColor('#000000')
                                ->penColorOnDark('#ffffff')
                                ->backgroundColor('#f3f4f6'),
                            SignaturePad::make('manager_signature')
                                ->label('Signature du Manager')
                                ->penColor('#000000')
                                ->penColorOnDark('#ffffff')
                                ->backgroundColor('#f3f4f6'),
                        ])->columns(2),
                ])->columnSpanFull(),
            ]);
    }
}
