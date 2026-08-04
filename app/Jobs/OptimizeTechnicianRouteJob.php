<?php

namespace App\Jobs;

use App\Models\RH\Employee;
use App\Models\User;
use App\Services\Interventions\RouteOptimizationService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizeTechnicianRouteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max

    public function __construct(
        public int $technicienId,
        public string $date,
        public ?int $userId = null
    ) {}

    public function handle(RouteOptimizationService $optimizer): void
    {
        $technicien = Employee::find($this->technicienId);
        
        if (!$technicien) {
            return;
        }

        $result = $optimizer->optimizeForTechnician($technicien, $this->date);

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                if ($result['success']) {
                    Notification::make()
                        ->success()
                        ->title('Optimisation terminée')
                        ->body($result['message'] . ' ' . $result['interventions_count'] . ' interventions réordonnées pour ' . $technicien->full_name . '.')
                        ->sendToDatabase($user);
                } else {
                    Notification::make()
                        ->danger()
                        ->title('Erreur d\'optimisation')
                        ->body('Pour ' . $technicien->full_name . ' : ' . $result['message'])
                        ->sendToDatabase($user);
                }
            }
        }
    }
}
