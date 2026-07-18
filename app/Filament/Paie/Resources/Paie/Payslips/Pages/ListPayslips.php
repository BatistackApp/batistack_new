<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use App\Jobs\Paie\GenerateMassPayslipsJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

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
            CreateAction::make(),
        ];
    }
}
