<?php

namespace App\Filament\Subcontractor\Resources;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\SubcontractorSituation;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class SubcontractorInvoiceResource extends Resource
{
    protected static ?string $model = SubcontractorSituation::class;

    protected static ?string $modelLabel = 'Facture de situation';
    protected static ?string $pluralModelLabel = 'Factures de situation';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if (!$user || !$user->contact || !$user->contact->thirdParty) {
            return parent::getEloquentQuery()->where('id', 0);
        }

        $subcontractorId = $user->contact->thirdParty->id;

        return parent::getEloquentQuery()->where('subcontractor_id', $subcontractorId);
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $subcontractorId = $user?->contact?->thirdParty?->id;

        return $schema
            ->components([
                Select::make('chantier_id')
                    ->label('Chantier concerné')
                    ->options(function () use ($subcontractorId) {
                        if (!$subcontractorId) return [];
                        // Find chantiers where subcontractor is allocated
                        return Chantier::whereHas('phases.tasks.allocations', function ($q) use ($subcontractorId) {
                            $q->where('allocatable_type', \App\Models\Tiers\ThirdParty::class)
                              ->where('allocatable_id', $subcontractorId);
                        })->pluck('reference', 'id');
                    })
                    ->required()
                    ->searchable(),

                TextInput::make('reference')
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
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->label('Avancement')
                    ->formatStateUsing(fn ($state) => $state . ' %'),
                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Date d\'envoi')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (SubcontractorSituation $record) => $record->status === InvoiceStatus::DRAFT),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (SubcontractorSituation $record) => $record->status === InvoiceStatus::DRAFT),
                Tables\Actions\Action::make('download')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractorInvoices::route('/'),
            'create' => Pages\CreateSubcontractorInvoice::route('/create'),
            'edit' => Pages\EditSubcontractorInvoice::route('/{record}/edit'),
        ];
    }
}
