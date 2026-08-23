<?php

namespace App\Filament\Paie\Resources\Paie\DsnSubmissions;

use App\Enums\Paie\DsnSubmissionStatus;
use App\Filament\Paie\Resources\Paie\DsnSubmissions\Pages\ListDsnSubmissions;
use App\Filament\Paie\Resources\Paie\DsnSubmissions\Pages\ViewDsnSubmission;
use App\Models\Paie\DsnSubmission;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

class DsnSubmissionResource extends Resource
{
    protected static ?string $model = DsnSubmission::class;

    protected static ?string $modelLabel = 'Soumission DSN';

    protected static ?string $pluralModelLabel = 'Soumissions DSN';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion de la Paie';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Période')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.legal_name')
                    ->label('Entreprise')
                    ->sortable(),
                Tables\Columns\TextColumn::make('export_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'csv_expert' => 'info',
                        'api_m2m' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (DsnSubmissionStatus $state): string => match ($state) {
                        DsnSubmissionStatus::DRAFT => 'gray',
                        DsnSubmissionStatus::EXPORTED => 'warning',
                        DsnSubmissionStatus::SUBMITTED => 'info',
                        DsnSubmissionStatus::PARTIAL => 'warning',
                        DsnSubmissionStatus::ACCEPTED => 'success',
                        DsnSubmissionStatus::REJECTED => 'danger',
                    }),
                Tables\Columns\TextColumn::make('payslips_count')
                    ->label('Bulletins')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_gross')
                    ->label('Brut total')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exported_at')
                    ->label('Exporté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créé par'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(DsnSubmissionStatus::class),
                Tables\Filters\SelectFilter::make('export_type')
                    ->label('Type d\'export')
                    ->options([
                        'csv_expert' => 'CSV Expert-comptable',
                        'api_m2m' => 'API M2M',
                    ]),
            ]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('period')
                            ->label('Période'),
                        TextEntry::make('company.legal_name')
                            ->label('Entreprise'),
                        TextEntry::make('export_type')
                            ->label('Type d\'export')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'csv_expert' => 'info',
                                'api_m2m' => 'success',
                                default => 'gray',
                            }),
                    ]),
                Section::make('Statut')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (DsnSubmissionStatus $state): string => match ($state) {
                                DsnSubmissionStatus::DRAFT => 'gray',
                                DsnSubmissionStatus::EXPORTED => 'warning',
                                DsnSubmissionStatus::SUBMITTED => 'info',
                                DsnSubmissionStatus::PARTIAL => 'warning',
                                DsnSubmissionStatus::ACCEPTED => 'success',
                                DsnSubmissionStatus::REJECTED => 'danger',
                            }),
                        TextEntry::make('exported_at')
                            ->label('Date d\'export')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('submitted_at')
                            ->label('Date de soumission')
                            ->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Synthèse financière')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('payslips_count')
                            ->label('Nombre de bulletins')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('total_gross')
                            ->label('Brut total')
                            ->money('EUR')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('total_net')
                            ->label('Net total')
                            ->money('EUR'),
                        TextEntry::make('total_employer_cost')
                            ->label('Coût employeur total')
                            ->money('EUR'),
                    ]),
                Section::make('Détail des bulletins')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('payslip.employee.last_name')
                                    ->label('Employé'),
                                TextEntry::make('payslip.employee.first_name')
                                    ->label('Prénom'),
                                TextEntry::make('payslip.period')
                                    ->label('Période'),
                                TextEntry::make('payslip.gross_salary')
                                    ->label('Brut')
                                    ->money('EUR'),
                                TextEntry::make('payslip.net_paid')
                                    ->label('Net payé')
                                    ->money('EUR'),
                                TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge(),
                                TextEntry::make('error_message')
                                    ->label('Erreur')
                                    ->color('danger'),
                            ]),
                    ]),
                Section::make('Erreur')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('Message d\'erreur')
                            ->columnSpanFull()
                            ->color('danger'),
                    ])
                    ->visible(fn (DsnSubmission $record) => filled($record->error_message)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDsnSubmissions::route('/'),
            'view' => ViewDsnSubmission::route('/{record}'),
        ];
    }
}
