<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Pages;

use App\Filament\Salarie\Resources\Paie\Payslips\PayslipResource;
use App\Models\Paie\Payslip;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Télécharger')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Payslip $record) => $record->pdf_path ? Storage::url($record->pdf_path) : null)
                ->openUrlInNewTab()
                ->visible(fn (Payslip $record) => ! empty($record->pdf_path)),
        ];
    }
}
