<?php

namespace App\Filament\Subcontractor\Resources;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Closure;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubcontractorInvoiceResource extends Resource
{
    protected static ?string $model = SubcontractorSituation::class;

    protected static ?string $modelLabel = 'Facture de situation';

    protected static ?string $pluralModelLabel = 'Factures de situation';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $subcontractor = $user?->contact?->thirdParty;

        if (! $subcontractor || $subcontractor->type !== ThirdPartyType::SUBCONTRACTOR) {
            return parent::getEloquentQuery()->where('id', 0);
        }

        $subcontractorId = $subcontractor->id;

        return parent::getEloquentQuery()->where('subcontractor_id', $subcontractorId);
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $subcontractor = $user?->contact?->thirdParty;
        $subcontractorId = $subcontractor?->type === ThirdPartyType::SUBCONTRACTOR
            ? $subcontractor->id
            : null;

        return $schema
            ->components([
                Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier concerné')
                    ->options(function () use ($subcontractorId) {
                        if (! $subcontractorId) {
                            return [];
                        }

                        // Find chantiers where subcontractor is allocated
                        return Chantier::whereHas('phases.tasks.allocations', function ($q) use ($subcontractorId) {
                            $q->where('allocatable_type', ThirdParty::class)
                                ->where('allocatable_id', $subcontractorId);
                        })->pluck('reference', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->rules([
                        function (string $attribute, mixed $value, Closure $fail) use ($subcontractorId): void {
                            $isAssignedToSubcontractor = static::chantierIsAssignedToSubcontractor(
                                $value,
                                $subcontractorId
                            );

                            if (! $isAssignedToSubcontractor) {
                                $fail('Le chantier sélectionné n’est pas attribué à votre entreprise.');
                            }
                        },
                    ]),

                TextInput::make('reference')->label('Référence')
                    ->label('Numéro de facture')
                    ->required()
                    ->maxLength(255),

                TextInput::make('progress_percentage')
                    ->label('Pourcentage facturé (%)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),

                TextInput::make('total_ht')
                    ->label('Montant HT (€)')
                    ->numeric()
                    ->required(),

                SpatieMediaLibraryFileUpload::make('invoice_document')
                    ->label('Document de la facture (PDF)')
                    ->collection('invoice_document')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->label('Avancement')
                    ->formatStateUsing(fn ($state) => $state.' %'),
                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR'),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('created_at')->label('Créé le')
                    ->label('Date d\'envoi')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (SubcontractorSituation $record) => $record->status === InvoiceStatus::DRAFT),
                DeleteAction::make()
                    ->visible(fn (SubcontractorSituation $record) => $record->status === InvoiceStatus::DRAFT),
                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (SubcontractorSituation $record) {
                        $media = $record->getFirstMedia('invoice_document');
                        if ($media) {
                            return response()->download($media->getPath(), $media->file_name);
                        }
                    })
                    ->visible(fn (SubcontractorSituation $record) => $record->hasMedia('invoice_document')),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function chantierIsAssignedToSubcontractor(int|string $chantierId, ?int $subcontractorId): bool
    {
        if (! $subcontractorId) {
            return false;
        }

        return Chantier::whereKey($chantierId)
            ->whereHas('phases.tasks.allocations', function ($query) use ($subcontractorId) {
                $query->where('allocatable_type', ThirdParty::class)
                    ->where('allocatable_id', $subcontractorId);
            })
            ->exists();
    }

    public static function canAccess(): bool
    {
        $subcontractor = auth()->user()?->contact?->thirdParty;

        return $subcontractor?->type === ThirdPartyType::SUBCONTRACTOR;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractorInvoices::route('/'),
            'create' => Pages\CreateSubcontractorInvoice::route('/create'),
            'edit' => Pages\EditSubcontractorInvoice::route('/{record}/edit'),
        ];
    }
}
