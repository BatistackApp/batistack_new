<?php

namespace App\Exceptions\Articles;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class ArticlesModuleException extends Exception
{
    public function report(): void
    {
        Log::error("ArticlesModuleException: {$this->getMessage()}", [
            'exception' => $this,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    public function render(Request $request): Response|JsonResponse
    {
        $status = ($this->getCode() >= 400 && $this->getCode() <= 599)
            ? $this->getCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ], $status);
        }

        return new Response($this->getMessage(), $status);
    }

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
