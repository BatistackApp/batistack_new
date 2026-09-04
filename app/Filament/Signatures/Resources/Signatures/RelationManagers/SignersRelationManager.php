<?php

namespace App\Filament\Signatures\Resources\Signatures\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Mail\Core\MultiSignatureRequestedMail;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SignersRelationManager extends RelationManager
{
    protected static string $relationship = 'signers';

    protected static ?string $title = 'Signataires';

    protected static string|\BackedEnum|null $icon = Phosphor::Users;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('signed_at')
                    ->label('Signé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('copyLink')
                    ->label('Copier le lien')
                    ->icon(Phosphor::Link)
                    ->color('gray')
                    ->action(function ($record) {
                        $url = route('signature.show', $record->token);

                        Notification::make()
                            ->title('Lien copié')
                            ->body($url)
                            ->success()
                            ->send();
                    }),
                Action::make('resendEmail')
                    ->label('Renvoyer l\'email')
                    ->icon(Phosphor::ArrowClockwise)
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === SignatureStatus::PENDING)
                    ->action(function ($record) {
                        Mail::to($record->email)->send(new MultiSignatureRequestedMail($record));

                        Notification::make()
                            ->title('Email renvoyé')
                            ->body("Demande renvoyée à {$record->email}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
