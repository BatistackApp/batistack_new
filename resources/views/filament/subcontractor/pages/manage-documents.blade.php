<x-filament-panels::page>
    <div class="mb-4">
        @if($isCompliant)
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-6 h-6 mr-2" />
                    <span class="font-medium">Statut : Conforme</span>
                </div>
                <div class="mt-2">
                    Votre dossier de vigilance est complet. L'entreprise principale peut vous confier des chantiers en toute légalité.
                </div>
            </div>
        @else
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                    <span class="font-medium">Statut : Dossier Incomplet</span>
                </div>
                <div class="mt-2">
                    Certains documents obligatoires sont manquants ou expirés. Veuillez déposer les fichiers manquants ci-dessous pour régulariser votre situation.
                </div>
            </div>
        @endif
    </div>

    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit">
                Enregistrer les documents
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
