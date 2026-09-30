<?php

namespace App\Filament\RH\Resources\Employees\Pages;

use App\Exceptions\RH\EmployeePinException;
use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Models\RH\Employee;
use App\Services\RH\EmployeePinService;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Auth\Access\AuthorizationException;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('print')
                ->label('Imprimer la fiche')
                ->color('gray')
                ->icon(Phosphor::Printer)
                ->action(fn (Employee $record, RHDocumentService $service) => $service->download($service->generateFullRecord($record))),
            Action::make('reset_pin')
                ->label('Réinitialiser le PIN')
                ->icon(Phosphor::Key)
                ->color('warning')
                ->authorize('update')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser le code PIN')
                ->modalDescription('Un code PIN provisoire aléatoire sera généré et envoyé par email au salarié. Il devra le modifier depuis son espace Mon Profil → Code PIN.')
                ->action(function (Employee $record, EmployeePinService $service) {
                    try {
                        $service->resetPin($record);
                    } catch (AuthorizationException $exception) {
                        Notification::make()
                            ->title('Opération refusée')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    } catch (EmployeePinException $exception) {
                        Notification::make()
                            ->title('Échec de la réinitialisation')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('PIN réinitialisé')
                        ->success()
                        ->body('Un email contenant le code provisoire a été envoyé au salarié.')
                        ->send();
                }),
        ];
    }
}
