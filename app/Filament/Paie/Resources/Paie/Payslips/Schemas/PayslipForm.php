<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Schemas;

use Filament\Forms\Components\DatePicker;
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
                TextInput::make('hourly_rate')
                    ->required()
                    ->numeric(),
                TextInput::make('gross_salary')
                    ->required()
                    ->numeric(),
                TextInput::make('net_social')
                    ->required()
                    ->numeric(),
                TextInput::make('taxable_net')
                    ->required()
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
                    ->required()
                    ->numeric(),
                TextInput::make('net_paid')
                    ->required()
                    ->numeric(),
                TextInput::make('employer_cost')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('pdf_path'),
                DatePicker::make('payment_date'),
            ]);
    }
}
