<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts\Pages;

use App\Filament\Banque\Resources\Banque\BankAccounts\BankAccountResource;
use App\Services\Banque\BridgeApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_bridge')
                ->label('Gérer mes connexions bancaires')
                ->icon('heroicon-o-building-library')
                ->color('primary')
                ->action(function (BridgeApiService $bridgeService) {
                    $user = auth()->user();
                    if (! $user) {
                        Notification::make()
                            ->title('Erreur')
                            ->body('Authentification requise.')
                            ->danger()
                            ->send();

                        return;
                    }
                    $company = \App\Models\Core\Company::first();
                    $externalUserId = 'company_'.$company->id;
                    $userEmail = $user->email;
                    $callbackUrl = route('bridge.callback');

                    try {
                        $url = $bridgeService->createManagementSessionUrl($externalUserId, $userEmail, $callbackUrl);

                        return redirect($url);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur Bridge API')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
