<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\MedicalVisiteType;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MedicalVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'medicalVisits';

    protected static ?string $title = 'Suivi Médical (VIP/SIR)';

    protected static string|BackedEnum|null $icon = Phosphor::Heart;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')->options(MedicalVisiteType::class)->required(),
                DatePicker::make('visit_date')->label('Date de visite')->required()->native(false),
                DatePicker::make('next_due_date')->label('Prochaine échéance')->required()->native(false),
                Select::make('aptitude')->options(MedicalAptitude::class)->required(),
                TextInput::make('practitioner_name')->label('Médecin / Centre'),
                Textarea::make('notes')->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('certificate')->collection('medical_certs')->label('Fiche d\'aptitude (PDF)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('visit_date')->label('Date')->date('d/m/Y'),
                TextColumn::make('type')->badge(),
                TextColumn::make('aptitude')->badge(),
                TextColumn::make('next_due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state->isPast() ? 'danger' : ($state->diffInDays(now()) < 30 ? 'warning' : 'success'))
                    ->weight('bold'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nouvelle Visite')
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
