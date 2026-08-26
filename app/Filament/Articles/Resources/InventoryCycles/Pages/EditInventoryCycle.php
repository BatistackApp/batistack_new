<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Pages;

use App\Enums\Articles\InventoryCycleStatus;
use App\Filament\Articles\Resources\InventoryCycles\InventoryCycleResource;
use App\Services\Articles\CycleCountingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventoryCycle extends EditRecord
{
    protected static string $resource = InventoryCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Soumettre pour validation')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status->value, [InventoryCycleStatus::PENDING->value, InventoryCycleStatus::IN_PROGRESS->value]))
                ->action(function (CycleCountingService $service) {
                    $service->submitForReview($this->record);
                    Notification::make()->title('Cycle soumis')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('approve')
                ->label('Approuver et Ajuster le Stock')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === InventoryCycleStatus::PENDING_REVIEW)
                ->action(function (CycleCountingService $service) {
                    $service->approveCycle($this->record, auth()->user());
                    Notification::make()->title('Inventaire approuvé')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
