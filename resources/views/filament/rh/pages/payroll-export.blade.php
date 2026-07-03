<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="export">
            {{ $this->form }}

            <div class="mt-6">
                {{ $this->exportAction }}
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
