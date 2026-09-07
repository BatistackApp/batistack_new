<?php

namespace App\Filament\Signatures\Resources\Signatures\Schemas;

use App\Enums\Core\SignatureStatus;
use App\Models\Core\Signature;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\HeroIcon;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SignatureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('signature_progress')
                    ->label('Progression des signatures')
                    ->content(function (Signature $record) {
                        $total = $record->signers()->count();
                        $signed = $record->signers()->where('status', SignatureStatus::SIGNED)->count();
                        $pending = $record->signers()->where('status', SignatureStatus::PENDING)->count();

                        return "{$signed}/{$total} signée(s) — {$pending} en attente";
                    })
                    ->columnSpanFull(),

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

                Section::make('Document signé')
                    ->icon(HeroIcon::DocumentCheck)
                    ->visible(fn (Signature $record) => $record->status === SignatureStatus::SIGNED)
                    ->schema([
                        Placeholder::make('stamped_document_link')
                            ->label('PDF signé')
                            ->content(function (Signature $record) {
                                $url = $record->stamped_document_url;

                                if (! $url) {
                                    return 'Document non disponible';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<a href="' . e($url) . '" target="_blank" class="text-primary-600 dark:text-primary-400 underline hover:no-underline">' .
                                    '📄 Ouvrir le PDF signé' .
                                    '</a>'
                                );
                            })
                            ->columnSpanFull(),
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

                Section::make('Signatures manuscrites')
                    ->icon(Phosphor::PencilSimpleLine)
                    ->visible(fn (Signature $record) => $record->signers()->count() > 0)
                    ->schema([
                        Placeholder::make('signer_signatures')
                            ->label('Signatures des signataires')
                            ->content(function (Signature $record) {
                                $signers = $record->signers()->where('status', SignatureStatus::SIGNED)->get();

                                if ($signers->isEmpty()) {
                                    return 'Aucune signature enregistrée.';
                                }

                                $html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">';

                                foreach ($signers as $signer) {
                                    $html .= '<div class="border rounded-lg p-3 bg-white dark:bg-gray-800">';
                                    $html .= '<p class="text-sm font-semibold mb-1">' . e($signer->name) . '</p>';
                                    $html .= '<p class="text-xs text-gray-500 mb-2">' . e($signer->role) . ' — ' . $signer->signed_at->format('d/m/Y H:i') . '</p>';

                                    if ($signer->signature_data) {
                                        $html .= '<img src="' . e($signer->signature_data) . '" alt="Signature de ' . e($signer->name) . '" class="max-h-20 border bg-white" />';
                                    } else {
                                        $html .= '<p class="text-xs text-gray-400 italic">Pas d\'image</p>';
                                    }

                                    $html .= '</div>';
                                }

                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
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
