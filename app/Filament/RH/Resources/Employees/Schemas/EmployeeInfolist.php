<?php

namespace App\Filament\RH\Resources\Employees\Schemas;

use App\Models\RH\Employee;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                SpatieMediaLibraryImageEntry::make('avatar')
                                    ->label('')
                                    ->collection('avatar')
                                    ->circular()
                                    ->grow(false),

                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('full_name')
                                            ->label('Nom Complet')
                                            ->weight('bold')
                                            ->size('lg'),
                                        TextEntry::make('registration_number')
                                            ->label('Matricule')
                                            ->fontFamily('mono'),
                                    ])->columnSpan(2),

                                Grid::make(1)
                                    ->schema([
                                        IconEntry::make('is_active')
                                            ->label('Statut')
                                            ->boolean(),
                                        TextEntry::make('currentContract.job_title')
                                            ->label('Poste occupé')
                                            ->badge()
                                            ->color('info'),
                                    ])->columnSpan(1),
                            ]),
                    ]),

                Tabs::make('Détails du Dossier')
                    ->tabs([
                        // --- INFOS ADMINISTRATIVES ---
                        Tabs\Tab::make('Administration')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('birth_date')
                                            ->label('Date de naissance')
                                            ->date('d/m/Y'),
                                        TextEntry::make('social_security_number')
                                            ->label('N° Sécurité Sociale')
                                            ->fontFamily('mono'),
                                        TextEntry::make('email')->label('Email')
                                            ->label('Email pro')
                                            ->icon(Phosphor::Envelope)
                                            ->copyable(),
                                        TextEntry::make('phone')->label('Téléphone')
                                            ->label('Téléphone')
                                            ->icon(Phosphor::Phone),
                                    ]),
                            ]),

                        // --- COMPÉTENCES & HABILITATIONS ---
                        Tabs\Tab::make('Habilitations')
                            ->icon(Phosphor::ShieldCheck)
                            ->schema([
                                Section::make('Portefeuille de sécurité')
                                    ->description('Liste des certifications et habilitations techniques (CACES, Électrique, etc.)')
                                    ->schema([
                                        // On utilise une entrée simple ici car le détail est dans le RelationManager
                                        TextEntry::make('qualifications_summary')
                                            ->label('Nombre d\'habilitations actives')
                                            ->getStateUsing(fn (Employee $record) => $record->qualifications()->where('expires_at', '>', now())->count())
                                            ->badge()
                                            ->color('success')
                                            ->icon(Phosphor::CheckCircle),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
