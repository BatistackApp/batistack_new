<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class ReservesRelationManager extends RelationManager
{
    protected static string $relationship = 'reserves';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Réserves / OPR';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Objet de la réserve')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Select::make('severity')
                    ->label('Gravité')
                    ->options(ReserveSeverity::class)
                    ->default(ReserveSeverity::MINOR->value)
                    ->required(),
                Select::make('assigned_to')
                    ->label('Assigné à')
                    ->relationship('assignee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->nullable(),
                DatePicker::make('due_date')
                    ->label('Échéance'),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->label('Photos')
                    ->collection('photos')
                    ->image()
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('#')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Objet')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Gravité')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.full_name')
                    ->label('Assigné à')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lifted_by')
                    ->label('Levée par')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ChantierReserveStatus::class),
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Gravité')
                    ->options(ReserveSeverity::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign')
                    ->label('Assigner')
                    ->icon('heroicon-o-user')
                    ->color('warning')
                    ->visible(fn (ChantierReserve $record) => $record->status === ChantierReserveStatus::OPEN)
                    ->form([
                        Select::make('assigned_to')
                            ->label('Assigné à (employé)')
                            ->relationship('assignee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Échéance'),
                    ])
                    ->action(function (ChantierReserve $record, array $data): void {
                        $record->update([
                            'assigned_to' => $data['assigned_to'],
                            'due_date' => $data['due_date'] ?? $record->due_date,
                            'status' => ChantierReserveStatus::IN_PROGRESS,
                        ]);
                        Notification::make()->title('Réserve assignée')->success()->send();
                    }),
                Tables\Actions\Action::make('resolve')
                    ->label('Marquer résolue')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChantierReserve $record) => in_array($record->status, [ChantierReserveStatus::OPEN, ChantierReserveStatus::IN_PROGRESS]))
                    ->requiresConfirmation()
                    ->action(function (ChantierReserve $record): void {
                        $record->update([
                            'status' => ChantierReserveStatus::RESOLVED,
                            'resolved_at' => now(),
                        ]);
                        Notification::make()->title('Réserve résolue')->success()->send();
                    }),
                Tables\Actions\Action::make('lift')
                    ->label('Levée par le client')
                    ->icon('heroicon-o-stamp')
                    ->color('primary')
                    ->visible(fn (ChantierReserve $record) => $record->status === ChantierReserveStatus::RESOLVED)
                    ->schema([
                        TextInput::make('lifted_by')
                            ->label('Nom du client')
                            ->required(),
                        SignaturePad::make('signature')
                            ->label('Signature du client')
                            ->required(),
                    ])
                    ->action(function (ChantierReserve $record, array $data): void {
                        $record->update([
                            'status' => ChantierReserveStatus::LIFTED,
                            'lifted_at' => now(),
                            'lifted_by' => $data['lifted_by'],
                        ]);

                        if (! empty($data['signature'])) {
                            $record->clearMediaCollection('signature');
                            $signature = $data['signature'];
                            $contents = $signature;
                            if (str_starts_with($signature, 'data:image')) {
                                $contents = base64_decode(substr($signature, strpos($signature, ',') + 1));
                            }

                            $record->addMediaFromString($contents)
                                ->usingFileName('signature-'.$record->id.'-'.now()->timestamp.'.png')
                                ->toMediaCollection('signature');
                        }

                        Notification::make()->title('Réserve levée')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
