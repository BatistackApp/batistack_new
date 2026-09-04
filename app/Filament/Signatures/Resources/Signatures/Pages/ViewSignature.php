<?php

namespace App\Filament\Signatures\Resources\Signatures\Pages;

use App\Enums\Core\SignatureStatus;
use App\Filament\Signatures\Resources\Signatures\SignatureResource;
use App\Mail\Core\MultiSignatureRequestedMail;
use App\Models\Core\Signature;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewSignature extends ViewRecord
{
    protected static string $resource = SignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label('Relancer')
                ->icon(Phosphor::ArrowClockwise)
                ->color('warning')
                ->visible(fn (Signature $record) => $record->status === SignatureStatus::PENDING)
                ->requiresConfirmation()
                ->modalHeading('Relancer les demandes')
                ->modalDescription('Les emails de demande seront renvoyés à tous les signataires en attente.')
                ->action(function (Signature $record) {
                    $pendingSigners = $record->signers()
                        ->where('status', SignatureStatus::PENDING)
                        ->get();

                    foreach ($pendingSigners as $signer) {
                        Mail::to($signer->email)->send(new MultiSignatureRequestedMail($signer));
                    }

                    Notification::make()
                        ->title('Demandes relancées')
                        ->body(count($pendingSigners).' email(s) renvoyé(s).')
                        ->success()
                        ->send();
                }),
            Action::make('cancel')
                ->label('Annuler la signature')
                ->icon(Phosphor::XCircle)
                ->color('danger')
                ->visible(fn (Signature $record) => $record->status === SignatureStatus::PENDING)
                ->requiresConfirmation()
                ->modalHeading('Annuler la signature')
                ->modalDescription('La demande de signature sera annulée. Cette action est irréversible.')
                ->action(function (Signature $record) {
                    $record->update(['status' => SignatureStatus::EXPIRED]);
                    $record->signers()
                        ->where('status', SignatureStatus::PENDING)
                        ->update(['status' => SignatureStatus::EXPIRED]);

                    Notification::make()
                        ->title('Signature annulée')
                        ->send();
                }),
        ];
    }
}
