<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class ReleaseNotesButton extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $version;

    public function mount(): void
    {
        $this->version = config('app.version', 'v0.0.0 (Développement)');
    }

    public function viewReleaseNotesAction(): Action
    {
        return Action::make('viewReleaseNotes')
            ->label('Notes de mise à jour')
            ->icon('heroicon-m-document-text')
            ->iconButton()
            ->tooltip('Notes de mise à jour')
            ->modalHeading('Notes de version - '.$this->version)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalContent(function (Component $livewire) {
                $repo = env('GITHUB_REPO', 'BatistackApp/batistack_new');

                $tag = explode(' ', $livewire->version)[0];

                try {
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
                            'content' => Str::markdown($body, [
                                'html_input' => 'strip',
                                'allow_unsafe_links' => false,
                            ]),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::error('GitHub Release Fetch Error: '.$e->getMessage());
                }

                return view('filament.widgets.release-notes-content', [
                    'content' => '<p>Impossible de récupérer les notes de version depuis GitHub.</p>',
                ]);
            });
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div class="ms-2 flex items-center">
            {{ $this->getAction('viewReleaseNotes') }}

            <x-filament-actions::modals />
        </div>
        BLADE;
    }
}
