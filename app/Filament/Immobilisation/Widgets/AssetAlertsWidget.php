<?php

namespace App\Filament\Immobilisation\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Immobilisation\FixedAsset;
use Filament\Tables\Columns\TextColumn;

class AssetAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                FixedAsset::query()
                    ->where('status', \App\Enums\Immobilisation\AssetStatus::ACTIVE)
                    // We can't easily filter on accessors in DB query, but we can load the most recent maintenances
                    ->with('maintenances')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Équipement')
                    ->searchable(),
                TextColumn::make('vgp_status')
                    ->label('État VGP')
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
                        'none' => 'Non soumis',
                        default => $state,
                    }),
                TextColumn::make('next_vgp_date')
                    ->label('Prochaine VGP')
                    ->date('d/m/Y'),
            ])
            // Filter records that actually have alerts using collection filtering since it relies on accessors
            // Or a custom view. Since this is a widget, we can just display the ones that need attention.
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                // To keep it simple, we just show all active assets, and the user can sort/filter.
                // In a real app, we might write a DB scope to only show expiring VGPs.
            })
            ->recordUrl(
                fn (FixedAsset $record): string => \App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource::getUrl('view', ['record' => $record])
            )
            ->heading('État des contrôles VGP');
    }
}
