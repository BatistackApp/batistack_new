<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Schemas;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseAdvance;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de la note')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('month')
                            ->label('Mois')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create()->day(1)->month($m)->translatedFormat('F')])->toArray()
                            )
                            ->required(),
                        TextInput::make('year')
                            ->label('Année')
                            ->required(),
                        TextInput::make('status')->label('Statut')
                            ->label('Statut')
                            ->disabled(),
                        Select::make('advance_ids')
                            ->label('Avances à déduire')
                            ->multiple()
                            ->options(function () {
                                $employeeId = Employee::where('user_id', auth()->id())->value('id');

                                return ExpenseAdvance::where('employee_id', $employeeId)
                                    ->where('status', ExpenseAdvanceStatus::PAID)
                                    ->pluck('reason', 'id');
                            })
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->helperText('Sélectionnez les avances déjà versées qui couvrent ce déplacement.'),
                    ])->columns(3),
            ]);
    }
}
