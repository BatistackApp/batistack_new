<?php

namespace App\Filament\Salarie\Resources;

use App\Filament\Salarie\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Salarie\Resources\ContractResource\Pages\ViewContract;
use App\Models\RH\Contract;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Mon Contrat';

    protected static ?string $modelLabel = 'Contrat';

    protected static ?string $pluralModelLabel = 'Contrats';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'job_title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', Auth::user()?->salarie?->id)
            ->latest('start_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations du contrat')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    TextEntry::make('type')
                        ->label('Type de contrat')
                        ->badge()
                        ->size(TextSize::Large),
                    TextEntry::make('category')
                        ->label('Catégorie')
                        ->badge(),
                    TextEntry::make('job_title')
                        ->label('Poste')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold),
                ]),

            Section::make('Dates')
                ->icon('heroicon-o-calendar')
                ->columns(3)
                ->schema([
                    TextEntry::make('start_date')
                        ->label('Date de début')
                        ->date('d/m/Y'),
                    TextEntry::make('end_date')
                        ->label('Date de fin')
                        ->date('d/m/Y')
                        ->placeholder('Durée indéterminée'),
                    TextEntry::make('trial_end_date')
                        ->label('Fin de période d\'essai')
                        ->date('d/m/Y')
                        ->placeholder('N/A'),
                ]),

            Section::make('Rémunération')
                ->icon('heroicon-o-currency-euro')
                ->columns(3)
                ->schema([
                    TextEntry::make('hourly_rate')
                        ->label('Taux horaire')
                        ->money('EUR'),
                    TextEntry::make('weekly_hours')
                        ->label('Heures hebdomadaires')
                        ->suffix(' h'),
                    TextEntry::make('salary')
                        ->label('Salaire mensuel estimé')
                        ->state(fn ($record) => $record->getSalary())
                        ->money('EUR')
                        ->weight(FontWeight::Bold)
                        ->color('success'),
                ]),

            Section::make('Signature')
                ->icon('heroicon-o-pencil')
                ->columns(2)
                ->schema([
                    TextEntry::make('signature_status')
                        ->label('Statut de la signature')
                        ->badge(),
                    TextEntry::make('payrollContributionProfile.label')
                        ->label('Profil de cotisation')
                        ->placeholder('Non défini'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Poste')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('signature_status')
                    ->label('Signature')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Tables\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'view' => ViewContract::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
