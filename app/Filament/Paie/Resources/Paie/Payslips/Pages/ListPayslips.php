<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use App\Jobs\Paie\GenerateMassPayslipsJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateMassPayslips')
                ->label('Générer en masse')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->schema([
                    TextInput::make('period')
                        ->label('Période (YYYY-MM)')
                        ->required()
                        ->default(now()->format('Y-m')),
                ])
                ->action(function (array $data) {
                    GenerateMassPayslipsJob::dispatch($data['period']);

                    Notification::make()
                        ->title('Génération en cours')
                        ->body("La génération en masse pour la période {$data['period']} a été lancée en arrière-plan.")
                        ->success()
                        ->send();
                }),
            Action::make('exportOdMonth')
                ->label('Export OD Comptable du mois')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->schema([
                    TextInput::make('period')
                        ->label('Période (YYYY-MM)')
                        ->required()
                        ->default(now()->format('Y-m')),
                ])
                ->action(function (array $data) {
                    $payslips = \App\Models\Paie\Payslip::where('period', $data['period'])
                        ->whereIn('status', [\App\Enums\Paie\PayslipStatus::VALIDATED, \App\Enums\Paie\PayslipStatus::PAID])
                        ->get();

                    if ($payslips->isEmpty()) {
                        Notification::make()
                            ->title('Aucun bulletin valide')
                            ->body("Il n'y a aucun bulletin validé ou payé pour la période {$data['period']}.")
                            ->warning()
                            ->send();
                        return;
                    }

                    $service = new \App\Services\Paie\AccountingExportService();
                    $path = $service->generateCsv($payslips);

                    return response()->download(storage_path('app/public/' . $path));
                }),
            Action::make('exportDsnMonth')
                ->label('Export DADS/DSN du mois')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Export DSN pour expert-comptable')
                ->modalDescription('Génère le fichier CSV et enregistre la soumission DSN pour la période sélectionnée.')
                ->modalSubmitActionLabel('Générer l\'export')
                ->schema([
                    TextInput::make('period')
                        ->label('Période (YYYY-MM)')
                        ->required()
                        ->default(now()->format('Y-m')),
                ])
                ->action(function (array $data) {
                    $payslips = \App\Models\Paie\Payslip::where('period', $data['period'])
                        ->whereIn('status', [\App\Enums\Paie\PayslipStatus::VALIDATED, \App\Enums\Paie\PayslipStatus::PAID])
                        ->get();

                    if ($payslips->isEmpty()) {
                        Notification::make()
                            ->title('Aucun bulletin valide')
                            ->body("Il n'y a aucun bulletin validé ou payé pour la période {$data['period']}.")
                            ->warning()
                            ->send();
                        return;
                    }

                    $companyId = $payslips->first()->employee->company_id ?? 1;

                    $service = new \App\Services\Paie\DsnExportService();
                    $submission = $service->generateForAccountant($payslips, $data['period'], $companyId, auth()->id());

                    auth()->user()->notify(new \App\Notifications\Paie\DsnExportedNotification($submission));

                    return Storage::disk('local')->download($submission->exported_file_path);
                }),
            CreateAction::make(),
        ];
    }
}
