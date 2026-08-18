<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $template?->name ?? 'Rapport d\'intervention' }}
        </x-slot>
        <x-slot name="description">
            {{ $template?->description }}
        </x-slot>

        <div class="mb-4 flex flex-wrap items-center gap-4 text-sm">
            <span class="font-semibold text-gray-600 dark:text-gray-300">Référence :</span>
            <span class="text-gray-800 dark:text-white">{{ $this->intervention->reference }}</span>
            <span class="font-semibold text-gray-600 dark:text-gray-300">Client :</span>
            <span class="text-gray-800 dark:text-white">{{ $this->intervention->thirdParty?->name }}</span>
            <span class="font-semibold text-gray-600 dark:text-gray-300">Statut :</span>
            <span class="text-gray-800 dark:text-white">{{ $this->getInterventionStatusLabel() }}</span>
        </div>

        @if($template)
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 text-right">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Enregistrer le rapport</span>
                        <span wire:loading>Enregistrement…</span>
                    </x-filament::button>
                </div>
            </form>
        @else
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-500/10 dark:text-amber-200">
                Aucun modèle de rapport actif ne correspond au type de cette intervention.
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>