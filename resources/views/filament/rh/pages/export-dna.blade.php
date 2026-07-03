<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                La Déclaration Nominative Annuelle (DNA) est obligatoire pour les entreprises du BTP affiliées à la Caisse des Congés Payés (CIBTP).
                Ce module exporte un fichier CSV structuré reprenant les heures travaillées et les salaires bruts perçus par chaque employé au cours de la période de référence.
            </p>
        </div>

        <form wire:submit="downloadDNA">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="phosphor-download" color="primary">
                    Générer & Télécharger la DNA (CSV)
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
