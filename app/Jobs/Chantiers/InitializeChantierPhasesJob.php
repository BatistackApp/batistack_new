<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitializeChantierPhasesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Chantier $chantier) {}

    public function handle(): void
    {
        $defaultPhases = [
            ['label' => 'Implantation', 'order' => 10],
            ['label' => 'Déchargement', 'order' => 20],
            ['label' => 'Montages & Installations', 'order' => 30],
            ['label' => 'Repli et Nettoyage', 'order' => 90],
        ];

        foreach ($defaultPhases as $phaseData) {
            $this->chantier->phases()->create($phaseData);
        }
    }
}
