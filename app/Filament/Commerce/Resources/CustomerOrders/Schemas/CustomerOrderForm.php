<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Schemas;

use App\Enums\Commerce\OrderStatus;
use App\Enums\Tiers\AddressType;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\CustomerOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CustomerOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de commande')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de commande')
                            ->readOnly()
                            ->default(fn (CustomerOrderService $service) => $service->generateReferenceOrder())
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('responsable_id')
                            ->label('Commercial')
                            ->options(User::admin()->get()->pluck('name', 'id'))
                            ->default(Auth::user()->id)
                            ->required(),

                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->relationship('chantier', 'reference')
                            ->searchable()
                            ->preload(),

                        Select::make('quote_id')
                            ->label('Devis d\'origine')
                            ->relationship('quote', 'reference')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(OrderStatus::class)
                            ->required()
                            ->default(OrderStatus::DRAFT)
                            ->native(false),

                        DatePicker::make('ordered_at')
                            ->label('Date de commande')
                            ->default(now())
                            ->required(),
                    ]),

                Section::make('Conditions')
                    ->schema([
                        Textarea::make('terms')
                            ->label('Conditions particulières')
                            ->rows(3),

                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->suffixActions([
                                Action::make('select')
                                    ->label('Définir')
                                    ->action(function (Get $get, Set $set) {
                                        $address = ThirdParty::find($get('client_id'))->addresses()->where('type', AddressType::DELIVERY)->first();
                                        if (!empty($address)) {
                                            $set('delivery_address', $address->full_address);
                                        } else {
                                            Notification::make()->danger()->title('Aucune adresse de livraison définie')->send();
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }
}
