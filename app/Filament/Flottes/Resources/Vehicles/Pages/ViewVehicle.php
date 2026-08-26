<?php

namespace App\Filament\Flottes\Resources\Vehicles\Pages;

use App\Filament\Flottes\Resources\Vehicles\VehicleResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qr_code')
                ->label('QR Code (État des lieux)')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->modalHeading('Scanner pour l\'état des lieux')
                ->modalDescription('Imprimez ce QR Code et placez-le dans le véhicule. Les chauffeurs pourront le scanner pour réaliser l\'état des lieux depuis leur mobile.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer')
                ->modalContent(function ($record) {
                    $url = url('/salarie/vehicules/'.$record->uuid.'/inspection');
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($url);

                    return new HtmlString('
                        <div class="flex flex-col items-center justify-center p-4">
                            <img src="'.$qrUrl.'" alt="QR Code" class="w-48 h-48 mb-4 border p-2 bg-white rounded-lg shadow-sm" />
                            <a href="'.$url.'" target="_blank" class="text-sm text-primary-600 hover:underline break-all text-center">'.$url.'</a>
                        </div>
                    ');
                }),
            EditAction::make(),
        ];
    }
}
