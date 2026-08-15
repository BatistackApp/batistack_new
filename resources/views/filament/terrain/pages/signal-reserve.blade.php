<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="flex items-center gap-x-3 mt-6">
            <x-filament::button type="submit" size="lg" color="danger">
                Signaler la réserve
            </x-filament::button>

            <x-filament::button href="/terrain" tag="a" color="gray">
                Annuler
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>