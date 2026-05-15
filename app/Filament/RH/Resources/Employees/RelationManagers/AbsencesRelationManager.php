<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Services\RH\LeaveBalanceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AbsencesRelationManager extends RelationManager
{
    protected static string $relationship = 'absences';

    protected static ?string $title = 'Suivi des Absences & Congés';

    protected static string|BackedEnum|null $icon = Phosphor::CalendarBlank;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Saisie de l\'absence')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->label('Nature de l\'absence')
                            ->options(AbsenceType::class)
                            ->required()
                            ->native(false),
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->label('Date de début')
                                    ->required()
                                    ->native(false),
                                DateTimePicker::make('end_date')
                                    ->label('Date de fin')
                                    ->required()
                                    ->native(false),
                            ]),
                        Textarea::make('reason')
                            ->label('Motif / Commentaire')
                            ->columnSpanFull(),
                        Toggle::make('is_paid')
                            ->label('Absence rémunérée')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('period')
                    ->label('Période')
                    ->getStateUsing(fn ($record) => "Du {$record->start_date->format('d/m/Y')} au {$record->end_date->format('d/m/Y')}"),
                TextColumn::make('duration')
                    ->label('Durée (Jours ouvrés)')
                    ->getStateUsing(function ($record) {
                        // Utilisation du service pour calculer les jours réels (excluant week-ends)
                        return app(LeaveBalanceService::class)->getConsumedDays($record->employee, $record->type);
                    })
                    ->suffix(' j'),
                IconColumn::make('is_paid')
                    ->label('Payé')
                    ->boolean(),

                TextColumn::make('cibtp_declared_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Déclaré le')
                    ->formatStateUsing(function (Abscence $record) {
                        return $record->cibtp_declared_at ? $record->cibtp_declared_at->format('d/m/Y') : 'Non Déclaré';
                    })
                    ->badge()
                    ->color(fn (Abscence $record) => $record->cibtp_declared_at ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()->label('Déclarer une absence'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('start_declaration_cibtp')
                    ->label('Déclarer l\'absence')
                    ->icon(Phosphor::ShieldCheck)
                    ->schema([
                        DatePicker::make('cibtp_declared_at')
                            ->label('Date de la déclaration')
                            ->required(),
                    ])
                    ->action(function (Abscence $record, array $data) {
                        $record->cibtp_declared_at = $data['cibtp_declared_at'];
                        $record->save();

                        return redirect('https://mon-espace.cibtp.fr/24/adh/connexion');
                    })
                    ->visible(fn (Abscence $record) => ! $record->cibtp_declared_at),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
