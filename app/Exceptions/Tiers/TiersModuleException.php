<?php

namespace App\Exceptions\Tiers;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\Response;

class TiersModuleException extends Exception
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code ?: $this->code, $previous);
    }

    public function notify()
    {
        return Notification::make()
            ->danger()
            ->title('Erreur dans le module tiers')
            ->body($this->message)
            ->send();
    }
}
