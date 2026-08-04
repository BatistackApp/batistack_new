<?php

namespace App\Jobs\Commerce;

use App\Mail\Commerce\CustomerStatementMail;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\CommerceDocumentationService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCustomerStatementEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300];

    public function __construct(
        protected int $clientId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?string $status = null,
        protected ?string $email = null,
    ) {}

    public function handle(CommerceDocumentationService $documents): void
    {
        $client = ThirdParty::with('primaryContact')->findOrFail($this->clientId);
        $email = $this->email ?: ($client->getPrimaryContact()?->email ?: $client->email);

        if (! $email) {
            Log::warning("No valid customer email for statement {$client->id}");

            return;
        }

        $startDate = $this->startDate ? Carbon::parse($this->startDate) : null;
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : null;

        $pdfPath = $documents->generateCustomerStatement(
            $client->id,
            $startDate,
            $endDate,
            $this->status,
        );

        Mail::to($email)->send(new CustomerStatementMail(
            $client,
            $pdfPath,
            $this->periodLabel($startDate, $endDate),
        ));

        Log::info("Customer statement sent to {$email}", [
            'client_id' => $client->id,
            'status' => $this->status,
        ]);
    }

    protected function periodLabel(?CarbonInterface $startDate, ?CarbonInterface $endDate): string
    {
        if (! $startDate && ! $endDate) {
            return 'Toutes periodes';
        }

        return sprintf(
            'du %s au %s',
            $startDate?->format('d/m/Y') ?? 'debut',
            $endDate?->format('d/m/Y') ?? 'aujourd\'hui',
        );
    }
}
