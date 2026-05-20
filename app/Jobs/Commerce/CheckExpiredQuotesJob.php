<?php

namespace App\Jobs\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\CustomerQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class CheckExpiredQuotesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting expired quotes check job...');

        // 1. Récupération des devis en statut SENT ou DRAFT avec expiration dépassée
        $expiredQuotes = CustomerQuote::whereIn('status', [QuoteStatus::SENT, QuoteStatus::DRAFT])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['client', 'user'])
            ->get();

        if ($expiredQuotes->isEmpty()) {
            Log::info('No expired quotes detected');
            return;
        }

        Log::info("Found {$expiredQuotes->count()} expired quotes");

        // 2. Mise à jour du statut et notifications
        foreach ($expiredQuotes as $quote) {
            // Passage en EXPIRED
            $quote->update([
                'status' => QuoteStatus::EXPIRED,
                'expired_at' => now(),
            ]);

            // Notification au commercial (créateur du devis)
            if ($quote->user) {
                $quote->user->notify(new QuoteExpiredNotification($quote));
            }

            Log::info("Quote {$quote->reference} marked as expired");
        }

        Log::info('Expired quotes check job completed');
    }
}
