<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Tables;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Accounting\FecExportService;
use App\Services\Immobilisation\AssetDisposalService;
use App\Services\Immobilisation\ImmobilisationDocumentService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FixedAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('purchase_date')
                    ->label('Date achat')
                    ->date()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Valeur brute')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('vgp_status')
                    ->label('VGP')
                    ->badge()
                    ->colors([
                        'success' => 'ok',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        'gray' => 'none',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ok' => 'À jour',
                        'warning' => 'Bientôt',
                        'danger' => 'Expirée',
                        'none' => 'N/A',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('last_inventoried_at')
                    ->label('Dernier inventaire')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('asset_category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(AssetStatus::class),
                SelectFilter::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_inventoried_recently')
                    ->label('Inventorié cette année ?')
                    ->placeholder('Tous les actifs')
                    ->trueLabel('Oui (scanné)')
                    ->falseLabel('Non (à vérifier)')
                    ->queries(
                        true: fn (Builder $query) => $query->where('last_inventoried_at', '>=', now()->subYear()),
                        false: fn (Builder $query) => $query->where('last_inventoried_at', '<', now()->subYear())->orWhereNull('last_inventoried_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('purchase_date')
                    ->schema([
                        DatePicker::make('purchased_from')->label('Acquis après le'),
                        DatePicker::make('purchased_until')->label('Acquis avant le'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['purchased_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date),
                            )
                            ->when(
                                $data['purchased_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Action::make('global_pdf')
                    ->label('État des dotations (PDF)')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('primary')
                    ->schema([
                        Select::make('year')
                            ->label('Exercice')
                            ->options(function () {
                                $years = [];
                                $current = now()->year;
                                for ($i = $current - 2; $i <= $current + 5; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $service = new ImmobilisationDocumentService;
                        $path = $service->generateGlobalDepreciationSchedule($data['year']);

                        return response()->download($path);
                    }),
                Action::make('inventory_pdf')
                    ->label('Fiche Inventaire (PDF)')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->schema([
                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier')
                            ->options(Chantier::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $chantier = Chantier::findOrFail($data['chantier_id']);
                        $service = new ImmobilisationDocumentService;
                        $path = $service->generateInventoryChecklist($chantier);

                        return response()->download($path);
                    }),
                Action::make('export_fec')
                    ->label('Export FEC (Amortissements)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->schema([
                        Select::make('year')
                            ->label('Exercice à exporter')
                            ->options(function () {
                                $years = [];
                                $current = now()->year;
                                for ($i = $current - 2; $i <= $current + 2; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $service = new FecExportService;
                        $path = $service->exportDepreciationsFec($data['year']);

                        return response()->download($path);
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print_sheet')
                        ->label('Imprimer Fiche')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->action(function (FixedAsset $record) {
                            $service = new ImmobilisationDocumentService;
                            $path = $service->generateAssetSheet($record);

                            return response()->download($path);
                        }),
                    Action::make('print_qr')
                        ->label('Imprimer QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('gray')
                        ->action(function (FixedAsset $record) {
                            $service = new ImmobilisationDocumentService;
                            $path = $service->generateQrLabel($record);

                            return $service->download($path);
                        }),
                    Action::make('generate_scan_qr')
                        ->label('QR Scan Terrain')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('primary')
                        ->action(function (FixedAsset $record) {
                            $url = url('/terrain/scan-materiel?token='.$record->qr_token);
                            $options = new QROptions([
                                'version' => 5,
                                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                                'eccLevel' => QRCode::ECC_L,
                                'scale' => 6,
                                'imageBase64' => true,
                            ]);
                            $qrData = (new QRCode($options))->render($url);

                            $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $qrData));

                            return response($decoded, 200, [
                                'Content-Type' => 'image/png',
                                'Content-Disposition' => 'inline; filename="qr-scan-'.$record->qr_token.'.png"',
                            ]);
                        }),
                    Action::make('dispose')
                        ->label('Céder / Rebut')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->schema([
                            DatePicker::make('disposal_date')->label('Date de sortie')->required()->default(now()),
                            TextInput::make('sale_price')->label('Prix de cession')->numeric()->default(0)->required()->prefix('€'),
                            TextInput::make('reason')->label('Raison (Revente, Vol, Rebut)')->required(),
                        ])
                        ->action(function (FixedAsset $record, array $data) {
                            $service = new AssetDisposalService;
                            $service->dispose($record, $data['disposal_date'], $data['sale_price'], $data['reason']);

                            // Génération du PV
                            $docService = new ImmobilisationDocumentService;
                            $path = $docService->generateDisposalCertificate($record);

                            return response()->download($path);
                        })
                        ->visible(fn (FixedAsset $record) => $record->status !== AssetStatus::DISPOSED),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('print_qrcodes')
                        ->label('Imprimer QR Codes')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $service = new ImmobilisationDocumentService;
                            $path = $service->generateQrCodeSheet($records);

                            return response()->download($path);
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
