<?php

namespace App\Exceptions\Commerce;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class AllocationOverflowException extends Exception
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
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response($this->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
