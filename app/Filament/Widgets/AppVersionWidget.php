<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AppVersionWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.app-version-widget';
    protected static ?int $sort = 100;

    public string $version;

    public function mount(): void
    {
        $this->version = config('app.version', 'v0.0.0 (Développement)');
    }

    public function viewReleaseNotesAction(): Action
    {
        return Action::make('viewReleaseNotes')
            ->label('Voir les notes de version')
            ->icon('heroicon-m-document-text')
            ->modalHeading('Notes de version - ' . $this->version)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalContent(function (\Livewire\Component $livewire) {
                $repo = env('GITHUB_REPO', 'BatistackApp/batistack_new');
                
                // Extrait le tag pur (ex: "v1.2.3" à partir de "v1.2.3 (Production)")
                $tag = explode(' ', $livewire->version)[0];

                try {
                    // Essayer de récupérer le tag spécifique, sinon fallback sur la dernière release
                    $response = Http::withHeaders([
                        'Accept' => 'application/vnd.github.v3+json',
                    ])->get("https://api.github.com/repos/{$repo}/releases/tags/{$tag}");

                    if ($response->failed()) {
                        $response = Http::withHeaders([
                            'Accept' => 'application/vnd.github.v3+json',
                        ])->get("https://api.github.com/repos/{$repo}/releases/latest");
                    }

                    if ($response->successful()) {
                        $body = $response->json('body') ?? 'Aucune note de version détaillée disponible.';
                        return view('filament.widgets.release-notes-content', [
                            'content' => Str::markdown($body)
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::error('GitHub Release Fetch Error: ' . $e->getMessage());
                }

                return view('filament.widgets.release-notes-content', [
                    'content' => '<p>Impossible de récupérer les notes de version depuis GitHub.</p>'
                ]);
            });
    }
}
