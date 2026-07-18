<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use App\Models\Paie\Payslip;
use App\Services\Paie\PayslipLockService;
use App\Services\Paie\PayslipPdfService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_pdf')
                ->label('Générer PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Payslip $record) {
                    $service = app(PayslipPdfService::class);
                    $service->generatePdf($record);

                    Notification::make()
                        ->title('PDF généré avec succès')
                        ->success()
                        ->send();
                }),
            Action::make('download_pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Payslip $record) => $record->pdf_path ? Storage::disk('public')->url($record->pdf_path) : null)
                ->openUrlInNewTab()
                ->visible(fn (Payslip $record) => $record->pdf_path !== null),
            Action::make('lock')
                ->label('Clôturer')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clôturer le bulletin')
                ->modalDescription('Êtes-vous sûr de vouloir clôturer ce bulletin ? Cette action est irréversible et figera les éléments de paie associés (pointages, acomptes). Un PDF définitif sera généré.')
                ->modalSubmitActionLabel('Oui, clôturer')
                ->visible(fn (Payslip $record) => $record->status === PayslipStatus::DRAFT)
                ->action(function (Payslip $record, PayslipLockService $lockService) {
                    $lockService->lock($record);
                    Notification::make()
                        ->title('Bulletin clôturé avec succès')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
