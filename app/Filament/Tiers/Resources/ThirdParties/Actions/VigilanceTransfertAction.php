<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Enums\Tiers\ThirdPartyType;
use App\Services\Tiers\VigilanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VigilanceTransfertAction
{
    public static function make(): Action
    {
        return Action::make('transfertVigilance')
            ->label('Transmettre les attestation')
            ->icon(Phosphor::FilePdf)
            ->visible(fn (Model $record) => $record->type === ThirdPartyType::SUBCONTRACTOR)
            ->schema([
                FileUpload::make('vigilance_attestation')
                    ->label('Attestation de Vigilance (URSSAF)')
                    ->disk('local')
                    ->visibility('private')
                    ->directory(fn (Model $record) => 'third_parties/'.$record->id.'/documents')
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => 'vigilance_attestation.'.$file->getClientOriginalExtension()),

                FileUpload::make('decennale_insurance')
                    ->label('Assurance Décennale')
                    ->disk('local')
                    ->visibility('private')
                    ->directory(fn (Model $record) => 'third_parties/'.$record->id.'/documents')
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => 'decennale_insurance.'.$file->getClientOriginalExtension()),

                FileUpload::make('kbis')
                    ->label('KBIS')
                    ->disk('local')
                    ->visibility('private')
                    ->directory(fn (Model $record) => 'third_parties/'.$record->id.'/documents')
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => 'kbis.'.$file->getClientOriginalExtension()),
            ])
            ->action(function (array $data, Model $record) {
                app(VigilanceService::class)->scanCompliance($record);

                Notification::make()
                    ->success()
                    ->title('Transmission des attestations')
                    ->body('Les attestations ont été transmises avec succès.')
                    ->send();
            });
    }
}
