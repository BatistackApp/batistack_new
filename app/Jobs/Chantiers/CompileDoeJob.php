<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\DoeDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CompileDoeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Chantier $chantier) {}

    public function handle(DoeDocumentService $service): void
    {
        try {
            // Compilation
            $zipPath = $service->compileDoe($this->chantier);

            // Alerte de réussite dans le centre de notifications Filament pour le manager
            if ($this->chantier->manager && $this->chantier->manager->user) {
                Notification::make()
                    ->success()
                    ->title('Compilation DOE terminée !')
                    ->body("L'archive du dossier d'ouvrages pour le chantier {$this->chantier->reference} est prête.")
                    ->icon(Phosphor::FileArchive)
                    ->actions([
                        Action::make('download')
                            ->label('Télécharger')
                            ->url(asset('storage/chantiers/doe/'.basename($zipPath)))
                            ->button(),
                    ])
                    ->sendToDatabase($this->chantier->manager->user);
            }

        } catch (\Exception $e) {
            Log::error("Erreur de compilation du DOE #{$this->chantier->id} : ".$e->getMessage());

            if ($this->chantier->manager && $this->chantier->manager->user) {
                Notification::make()
                    ->danger()
                    ->title('Échec de génération du DOE')
                    ->body($e->getMessage())
                    ->sendToDatabase($this->chantier->manager->user);
            }
        }
    }
}
