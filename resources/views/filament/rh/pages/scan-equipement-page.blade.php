<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        @if(data_get($this->data, 'equipement_info'))
            <div class="mt-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm">
                {{ data_get($this->data, 'equipement_info') }}
            </div>
        @endif

        <div class="mt-4">
            <x-filament::button type="submit" @class(['hidden' => !data_get($this->data, 'equipement_id')])>
                Valider
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
