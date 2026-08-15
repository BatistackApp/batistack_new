<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Schemas;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerOrder;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\QuoteService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                        TextInput::make('reference')->label('Référence')
                            ->label('Numéro de devis')
                            ->readOnly()
                            ->default(fn (QuoteService $service) => $service->generateReferenceQuote())
                            ->required(),

                        Select::make('responsable_id')
                            ->label('Commercial')
                            ->options(User::admin()->get()->pluck('name', 'id'))
                            ->default(Auth::user()->id)
                            ->required(),

                        Select::make('client_id')->label('Client')
                            ->label('Client')
                            ->options(ThirdParty::where('type', ThirdPartyType::CLIENT)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier')
                            ->relationship('chantier', 'reference')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Toggle::make('is_avenant')
                            ->label('Devis d\'avenant (travaux supplémentaires)')
                            ->helperText('Rattache ce devis à une commande principale pour faire évoluer le budget du chantier.')
                            ->default(false)
                            ->live(),

                        Select::make('parent_order_id')
                            ->label('Commande principale')
                            ->options(function (Get $get) {
                                $clientId = $get('client_id');
                                $chantierId = $get('chantier_id');

                                return CustomerOrder::query()
                                    ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
                                    ->when($chantierId, fn ($q) => $q->where('chantier_id', $chantierId))
                                    ->orderBy('reference')
                                    ->pluck('reference', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => (bool) $get('is_avenant'))
                            ->required(fn (Get $get) => (bool) $get('is_avenant')),

                        Select::make('status')->label('Statut')
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
