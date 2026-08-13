<?php

namespace App\Jobs\Tiers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Tiers\EmailCampaign;

class ProcessEmailCampaignJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600; // 10 minutes max
    
    protected $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(EmailCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->campaign->update(['status' => \App\Enums\Tiers\EmailCampaignStatus::SENDING]);
        $this->campaign->load('recipients');

        $hasFailures = false;

        foreach ($this->campaign->recipients()->where('status', \App\Enums\Tiers\EmailCampaignRecipientStatus::PENDING->value)->cursor() as $recipient) {
            try {
                \Illuminate\Support\Facades\Mail::to($recipient->email)->send(new \App\Mail\Tiers\GenericCampaignEmail($this->campaign->subject, $this->campaign->body));
                
                $recipient->update([
                    'status' => \App\Enums\Tiers\EmailCampaignRecipientStatus::SENT,
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                $hasFailures = true;
                $recipient->update([
                    'status' => \App\Enums\Tiers\EmailCampaignRecipientStatus::FAILED,
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        $this->campaign->update([
            'status' => $hasFailures ? \App\Enums\Tiers\EmailCampaignStatus::FAILED : \App\Enums\Tiers\EmailCampaignStatus::COMPLETED,
            'sent_at' => now(),
        ]);
    }
}
