<x-filament-panels::page>
    <div class="mb-6">
        @if($isCompliant)
            <div class="flex items-center gap-3 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                <x-heroicon-o-check-circle class="w-6 h-6 shrink-0" />
                <div>
                    <span class="font-medium">Statut : Conforme</span>
                    <p class="mt-1">Votre dossier de vigilance est complet. L'entreprise principale peut vous confier des chantiers en toute légalité.</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 shrink-0" />
                <div>
                    <span class="font-medium">Statut : Dossier Incomplet</span>
                    <p class="mt-1">Certains documents obligatoires sont manquants ou expirés.</p>
                    @if(count($issues) > 0)
                        <ul class="mt-2 list-disc list-inside">
                            @foreach($issues as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Enregistrer les documents
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
