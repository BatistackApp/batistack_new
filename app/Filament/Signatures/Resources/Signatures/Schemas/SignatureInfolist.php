<?php

namespace App\Filament\Signatures\Resources\Signatures\Schemas;

use App\Models\Core\Signature;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\HeroIcon;

class SignatureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->icon(HeroIcon::InformationCircle)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('signed_at')
                            ->label('Signé le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('user.name')
                            ->label('Créé par')
                            ->placeholder('—'),
                        TextEntry::make('token')
                            ->label('Token')
                            ->fontFamily('mono')
                            ->copyable(),
                    ]),

                Section::make('Document associé')
                    ->icon(HeroIcon::DocumentText)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('signable_type')
                            ->label('Type de document')
                            ->formatStateUsing(fn ($state) => class_basename($state)),
                        TextEntry::make('signable_id')
                            ->label('ID du document'),
                    ]),

                Section::make('Progression')
                    ->icon(HeroIcon::ChartBar)
                    ->schema([
                        TextEntry::make('progress')
                            ->label('Signature(s)')
                            ->state(function (Signature $record) {
                                $signed = $record->signed_count;
                                $total = $record->total_signers;

                                return "{$signed} / {$total} signataire(s)";
                            })
                            ->badge(function (Signature $record) {
                                if ($record->total_signers === 0) {
                                    return 'Aucun signataire';
                                }

                                if ($record->signed_count === $record->total_signers) {
                                    return 'Complété';
                                }

                                return 'En cours';
                            })
                            ->color(function (Signature $record) {
                                if ($record->total_signers === 0) {
                                    return 'gray';
                                }

                                return $record->signed_count === $record->total_signers ? 'success' : 'warning';
                            }),
                    ]),

                Section::make('Métadonnées')
                    ->icon(HeroIcon::CommandLine)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('checksum')
                            ->label('Checksum')
                            ->fontFamily('mono')
                            ->limit(64)
                            ->tooltip(fn (Signature $record) => $record->checksum),
                        TextEntry::make('metadata')
                            ->label('Métadonnées')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
