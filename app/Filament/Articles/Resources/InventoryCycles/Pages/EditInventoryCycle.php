<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Pages;

use App\Filament\Articles\Resources\InventoryCycles\InventoryCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryCycle extends EditRecord
{
    protected static string $resource = InventoryCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Soumettre pour validation')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status->value, [\App\Enums\Articles\InventoryCycleStatus::PENDING->value, \App\Enums\Articles\InventoryCycleStatus::IN_PROGRESS->value]))
                ->action(function (\App\Services\Articles\CycleCountingService $service) {
                    $service->submitForReview($this->record);
                    \Filament\Notifications\Notification::make()->title('Cycle soumis')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            \Filament\Actions\Action::make('approve')
                ->label('Approuver et Ajuster le Stock')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === \App\Enums\Articles\InventoryCycleStatus::PENDING_REVIEW)
                ->action(function (\App\Services\Articles\CycleCountingService $service) {
                    $service->approveCycle($this->record, auth()->user());
                    \Filament\Notifications\Notification::make()->title('Inventaire approuvé')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
