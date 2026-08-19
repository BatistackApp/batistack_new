<?php

namespace App\Filament\RH\Resources\TrainingSessions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\RH\TrainingSessionStatus;
use App\Enums\RH\OpcoStatus;
use App\Enums\RH\QualificationType;
use App\Enums\RH\CertificationSymbol;

class TrainingSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Détails & Qualification')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nom de la formation'),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                                DatePicker::make('started_at')
                                    ->required()
                                    ->label('Date de début'),
                                DatePicker::make('ended_at')
                                    ->required()
                                    ->label('Date de fin'),
                                Select::make('status')
                                    ->options(TrainingSessionStatus::class)
                                    ->required()
                                    ->default(TrainingSessionStatus::PLANIFIEE->value)
                                    ->label('Statut de la session'),
                                
                                Section::make('Qualification (Optionnel)')
                                    ->schema([
                                        Select::make('qualification_type')
                                            ->options(QualificationType::class)
                                            ->label('Type de qualification'),
                                        Select::make('certification_symbol')
                                            ->options(CertificationSymbol::class)
                                            ->label('Symbole de certification'),
                                        TextInput::make('validity_months')
                                            ->numeric()
                                            ->label('Durée de validité (mois)'),
                                    ])->columns(3),
                            ])->columns(2),
                        
                        Tabs\Tab::make('Budget & OPCO')
                            ->schema([
                                TextInput::make('cost')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0)
                                    ->label('Coût de la formation'),
                                TextInput::make('opco_reimbursement')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0)
                                    ->label('Prise en charge OPCO'),
                                Select::make('opco_status')
                                    ->options(OpcoStatus::class)
                                    ->required()
                                    ->default(OpcoStatus::NON_DEMANDE->value)
                                    ->label('Statut OPCO'),
                            ])->columns(3),
                    ])->columnSpanFull()
            ]);
    }
}
