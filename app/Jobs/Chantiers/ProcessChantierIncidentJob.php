<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\ChantierLog;
use App\Notifications\Chantiers\ChantierIncidentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessChantierIncidentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected ChantierLog $log) {}

    public function handle(): void
    {
        $manager = $this->log->chantier->manager;

        if ($manager) {
            $manager->notify(new ChantierIncidentNotification($this->log));
        }
    }
}
