<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Schemas;

use App\Enums\Laser\QuoteStatus;
use App\Models\Tiers\ThirdParty;
use App\Services\Laser\LaserQuoteService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de devis')
                            ->readOnly()
                            ->default(fn (LaserQuoteService $service) => $service->generateReference())
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->options(fn () => ThirdParty::where('type', 'client')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(QuoteStatus::class)
                            ->default(QuoteStatus::DRAFT)
                            ->required()
                            ->native(false),

                        DatePicker::make('expires_at')
                            ->label('Date d\'expiration')
                            ->default('+30 days')
                            ->required(),
                    ]),

                Section::make('Conditions')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('terms')
                            ->label('Conditions particulières')
                            ->rows(3),
                    ]),
            ]);
    }
}
