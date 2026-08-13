<?php

namespace App\Console\Commands\Tiers;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class ProcessScheduledCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiers:process-email-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled email campaigns and dispatch jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaigns = \App\Models\Tiers\EmailCampaign::where('status', \App\Enums\Tiers\EmailCampaignStatus::SCHEDULED->value)
            ->where(function($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now('Europe/Paris')->format('Y-m-d H:i:s'));
            })
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => \App\Enums\Tiers\EmailCampaignStatus::SENDING->value]);
            \App\Jobs\Tiers\ProcessEmailCampaignJob::dispatch($campaign);
            $this->info("Dispatched campaign: {$campaign->name}");
        }
    }
}
