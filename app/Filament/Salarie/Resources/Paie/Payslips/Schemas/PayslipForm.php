<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Schemas;

use App\Enums\Paie\PayslipStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayslipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_id')
                    ->required()
                    ->numeric(),
                TextInput::make('period')
                    ->required(),
                TextInput::make('base_hours')
                    ->required()
                    ->numeric(),
                TextInput::make('overtime_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('overtime_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('gd_allowance_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('expense_reports_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('custom_bonuses'),
                TextInput::make('meal_allowance_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('hourly_rate')
                    ->required()
                    ->numeric(),
                TextInput::make('gross_salary')
                    ->required()
                    ->numeric(),
                TextInput::make('net_social')
                    ->numeric(),
                TextInput::make('taxable_net')
                    ->numeric(),
                TextInput::make('pas_rate')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('pas_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('net_payable')
                    ->numeric(),
                TextInput::make('net_paid')
                    ->numeric(),
                TextInput::make('employer_cost')
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')->label('Statut')
                    ->options(PayslipStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('pdf_path'),
                DatePicker::make('payment_date'),
            ]);
    }
}
