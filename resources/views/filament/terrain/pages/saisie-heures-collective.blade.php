<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex items-center gap-x-3 mt-6">
            <x-filament::button type="submit" size="lg" color="success">
                Enregistrer (Brouillon)
            </x-filament::button>

            <x-filament::button type="button" size="lg" color="primary" wire:click="saveAndSubmit">
                Valider et soumettre
            </x-filament::button>

            <x-filament::button href="/terrain" tag="a" color="gray">
                Annuler
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
