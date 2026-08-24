<?php

namespace App\Filament\Customer\Resources\CustomerCreditNotes\Pages;

use App\Filament\Customer\Resources\CustomerCreditNotes\CustomerCreditNoteResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerCreditNote extends ViewRecord
{
    protected static string $resource = CustomerCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan(8)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label('Référence')
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('invoice.reference')
                                    ->label('Facture liée')
                                    ->icon(Phosphor::FileText)
                                    ->placeholder('—'),

                                TextEntry::make('created_at')
                                    ->label('Date de création')
                                    ->date('d/m/Y')
                                    ->icon(Phosphor::Calendar),
                            ]),

                        Section::make('Montants')
                            ->columnSpan(4)
                            ->schema([
                                TextEntry::make('total_ht')
                                    ->label('Total HT')
                                    ->money('EUR'),

                                TextEntry::make('total_ttc')
                                    ->label('Total TTC')
                                    ->money('EUR'),
                            ]),
                    ]),
            ]);
    }
}
