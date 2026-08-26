<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DigiposteWidget extends Widget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.salarie.widgets.digiposte-widget';

    public function getPayslips()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        return Payslip::where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->whereNotNull('pdf_path')
            ->latest('period')
            ->take(5)
            ->get();
    }

    public function getRhDocuments()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        return $employee->getMedia('rh_documents')
            ->sortByDesc('created_at')
            ->take(5);
    }

    public function getQualificationDocs()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        return $employee->qualifications()
            ->get()
            ->flatMap(function ($qualification) {
                return $qualification->getMedia('default')->map(function ($media) use ($qualification) {
                    return [
                        'name' => $qualification->label->value.' — '.$media->name,
                        'file_name' => $media->file_name,
                        'size' => $media->size,
                        'url' => Storage::url($media->getPath()),
                        'created_at' => $media->created_at,
                    ];
                });
            })
            ->sortByDesc('created_at')
            ->take(5)
            ->values();
    }

    public function getDocumentsTotal(): int
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return 0;
        }

        $payslipCount = Payslip::where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->whereNotNull('pdf_path')
            ->count();

        $rhDocCount = $employee->getMedia('rh_documents')->count();

        return $payslipCount + $rhDocCount;
    }

    public static function formatSize(?int $bytes): string
    {
        if (! $bytes || $bytes === 0) {
            return '0 o';
        }

        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
