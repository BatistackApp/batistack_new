<?php

namespace App\Filament\RH\Resources\RH\Interviews\Schemas;

use App\Enums\RH\InterviewStatus;
use App\Enums\RH\InterviewType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InterviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->required(),
                Select::make('manager_id')
                    ->relationship('manager', 'name')
                    ->required(),
                Select::make('type')
                    ->options(InterviewType::class)
                    ->required(),
                Select::make('status')
                    ->options(InterviewStatus::class)
                    ->default('planifie')
                    ->required(),
                DateTimePicker::make('scheduled_at')
                    ->required(),
                TextInput::make('evaluation_grid'),
                Textarea::make('employee_signature')
                    ->columnSpanFull(),
                Textarea::make('manager_signature')
                    ->columnSpanFull(),
            ]);
    }
}
