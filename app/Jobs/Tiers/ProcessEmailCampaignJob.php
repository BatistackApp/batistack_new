<?php

namespace App\Jobs\Tiers;

use App\Enums\Tiers\EmailCampaignRecipientStatus;
use App\Enums\Tiers\EmailCampaignStatus;
use App\Mail\Tiers\GenericCampaignEmail;
use App\Models\Tiers\EmailCampaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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
        $this->campaign->update(['status' => EmailCampaignStatus::SENDING]);
        $this->campaign->load('recipients');

        $hasFailures = false;

        foreach ($this->campaign->recipients()->where('status', EmailCampaignRecipientStatus::PENDING->value)->cursor() as $recipient) {
            try {
                Mail::to($recipient->email)->send(new GenericCampaignEmail($this->campaign->subject, $this->campaign->body));

                $recipient->update([
                    'status' => EmailCampaignRecipientStatus::SENT,
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                $hasFailures = true;
                $recipient->update([
                    'status' => EmailCampaignRecipientStatus::FAILED,
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        $this->campaign->update([
            'status' => $hasFailures ? EmailCampaignStatus::FAILED : EmailCampaignStatus::COMPLETED,
            'sent_at' => now(),
        ]);
    }
}
