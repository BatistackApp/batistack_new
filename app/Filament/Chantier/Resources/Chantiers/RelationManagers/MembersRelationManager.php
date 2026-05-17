<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Models\RH\Employee;
use App\Services\Chantiers\ChantierAnalyticService;
use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static string|BackedEnum|null $icon = Phosphor::UsersThree;

    protected static ?string $title = 'Équipe assignée';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')->label('Collaborateur')->searchable(),
                TextColumn::make('currentContract.job_title')->label('Poste'),
                TextColumn::make('compliance')
                    ->label('Sécurité')
                    ->getStateUsing(function (Employee $record) {
                        $check = app(ChantierAnalyticService::class)->canAssignEmployee($this->getOwnerRecord(), $record);

                        return $check['status'] ? 'Apte' : 'Alerte';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Apte' ? 'success' : 'danger'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Assigner un ouvrier')
                    ->preloadRecordSelect()
                    ->schema([
                        Select::make('recordId') // Le nom du champ doit être 'recordId' pour l'AttachAction
                            ->label('Sélectionner un employé')
                            ->options(Employee::all()->pluck('full_name', 'id')->toArray())
                            ->getOptionLabelsUsing(fn ($record) => $record->full_name.' '.$record->currentContract->job_title)
                            ->searchable()
                            ->preload(),
                    ])
                    ->after(function (array $data) {
                        $employee = Employee::find($data['recordId']);
                        $check = app(ChantierAnalyticService::class)->canAssignEmployee($this->getOwnerRecord(), $employee);
                        if (! $check['status']) {
                            Notification::make()
                                ->warning()
                                ->title('Risque sécurité')
                                ->body("L'employé assigné n'est pas à jour de ses visites médicales.")
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                DetachAction::make()->label('Retirer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Retirer'),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
