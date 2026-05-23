<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Schemas;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\QuoteService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CustomerQuoteForm
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
                            ->default(fn (QuoteService $service) => $service->generateReferenceQuote())
                            ->required(),

                        Select::make('responsable_id')
                            ->label('Commercial')
                            ->options(User::admin()->get()->pluck('name', 'id'))
                            ->default(Auth::user()->id)
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->options(ThirdParty::where('type', ThirdPartyType::CLIENT)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->relationship('chantier', 'reference')
                            ->searchable()
                            ->preload(),

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
            ]);
    }
}
