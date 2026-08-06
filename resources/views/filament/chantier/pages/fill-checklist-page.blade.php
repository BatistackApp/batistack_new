<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $template?->name }}
        </x-slot>
        <x-slot name="description">
            {{ $template?->description }}
        </x-slot>

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 text-right">
                <x-filament::button type="submit">
                    Enregistrer la checklist
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
