<?php

namespace App\Notifications\Accounting;

use App\Models\Accounting\AccountingSync;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Throwable;

class LaserAccountingSyncFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private AccountingSync $sync,
        private Throwable $exception,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $document = $this->sync->syncable;

        return FilamentNotification::make()
            ->danger()
            ->title('Synchronisation comptable Laser échouée')
            ->body('La synchronisation de '.$document?->reference.' a échoué après plusieurs tentatives. Relance manuelle nécessaire.')
            ->getDatabaseMessage();
    }
}
