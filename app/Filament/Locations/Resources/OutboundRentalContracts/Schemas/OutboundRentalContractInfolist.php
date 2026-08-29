<?php

namespace App\Filament\Locations\Resources\OutboundRentalContracts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OutboundRentalContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company_id')
                    ->numeric(),
                TextEntry::make('third_party_id')
                    ->numeric(),
                TextEntry::make('chantier_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reference'),
                TextEntry::make('status'),
                TextEntry::make('billing_period'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('expected_end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('actual_end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('daily_penalty_rate')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
