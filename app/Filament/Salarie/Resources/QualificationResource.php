<?php

namespace App\Filament\Salarie\Resources;

use App\Enums\RH\QualificationType;
use App\Filament\Salarie\Resources\QualificationResource\Pages\ListQualifications;
use App\Models\RH\Qualification;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QualificationResource extends Resource
{
    protected static ?string $model = Qualification::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Mes Qualifications';

    protected static ?string $modelLabel = 'Qualification';

    protected static ?string $pluralModelLabel = 'Qualifications';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'label';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', Auth::user()?->salarie?->id)
            ->latest('obtained_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations générales')
                ->icon('heroicon-o-academic-cap')
                ->columns(3)
                ->schema([
                    TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->size('lg'),
                    TextEntry::make('label')
                        ->label('Symbole / Certification')
                        ->weight('bold')
                        ->size('lg'),
                    TextEntry::make('reference_number')
                        ->label('N° Référence')
                        ->placeholder('Non renseigné'),
                ]),

            Section::make('Validité')
                ->icon('heroicon-o-clock')
                ->columns(3)
                ->schema([
                    TextEntry::make('obtained_at')
                        ->label('Date d\'obtention')
                        ->date('d/m/Y')
                        ->placeholder('Non renseigné'),
                    TextEntry::make('expires_at')
                        ->label('Date d\'expiration')
                        ->date('d/m/Y')
                        ->placeholder('Pas d\'échéance'),
                    TextEntry::make('days_until_expiration')
                        ->label('Jours restants')
                        ->state(function (Qualification $record): ?string {
                            if (is_null($record->expires_at)) {
                                return null;
                            }

                            return $record->getDaysUntilExpiration().' jours';
                        })
                        ->color(function (Qualification $record): ?string {
                            if (is_null($record->expires_at)) {
                                return null;
                            }

                            if ($record->isExpired()) {
                                return 'danger';
                            }

                            if ($record->isExpiringSoon()) {
                                return 'warning';
                            }

                            return 'success';
                        })
                        ->placeholder('N/A'),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Certification')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('N° Réf.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('obtained_at')
                    ->label('Obtention')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->state(function (Qualification $record): string {
                        if ($record->isExpired()) {
                            return 'expired';
                        }
                        if ($record->isExpiringSoon()) {
                            return 'expiring';
                        }

                        return 'active';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'expired' => 'danger',
                            'expiring' => 'warning',
                            'active' => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expired' => 'Expiré',
                        'expiring' => 'Bientôt expiré',
                        'active' => 'Valide',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(QualificationType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Valide',
                        'expiring' => 'Bientôt expiré',
                        'expired' => 'Expiré',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status'] ?? null, function (Builder $q, string $status) {
                            return match ($status) {
                                'active' => $q->active(),
                                'expired' => $q->expired(),
                                'expiring' => $q->expiringsSoon(90),
                                default => $q,
                            };
                        });
                    }),
            ])
            ->recordActions([
                Tables\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->emptyStateHeading('Aucune qualification')
            ->emptyStateDescription('Vous n\'avez aucune qualification enregistrée.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQualifications::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
