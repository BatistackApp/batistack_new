<?php

namespace App\Console\Commands;

use App\Models\RH\CibtpDeclaration;
use App\Models\User;
use App\Notifications\RH\CibtpDeadlineReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyCibtpDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cibtp:notify-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les déclarations CIBTP en retard et notifie les RH';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find declarations in draft status older than 15 days
        $urgentDeclarationsCount = CibtpDeclaration::where('status', 'draft')
            ->whereDate('date', '<=', now()->subDays(15))
            ->count();

        if ($urgentDeclarationsCount > 0) {
            $this->info("Found {$urgentDeclarationsCount} urgent CIBTP declarations.");

            // Get RH users / Admins
            $rhUsers = User::admin()->get();

            if ($rhUsers->isNotEmpty()) {
                Notification::send($rhUsers, new CibtpDeadlineReminderNotification($urgentDeclarationsCount));
                $this->info('Notifications sent successfully.');
            } else {
                $this->warn('No HR/Admin users found to notify.');
            }
        } else {
            $this->info('No urgent CIBTP declarations found.');
        }
    }
}
