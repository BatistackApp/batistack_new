<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $vehicle = $this->getVehicle();
        @endphp

        @if($vehicle)
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">Mon Véhicule Actuel</h2>
                    <p class="text-sm text-gray-500">{{ $vehicle->getDisplayName() }}</p>
                </div>
                <div>
                    <x-filament::button 
                        tag="a" 
                        href="{{ route('filament.salarie.pages.vehicules.{uuid}.inspection', ['uuid' => $vehicle->uuid]) }}"
                        icon="heroicon-o-camera"
                        color="primary"
                    >
                        Faire l'état des lieux
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="text-center py-4 text-gray-500">
                <p>Vous n'avez aucun véhicule assigné actuellement.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
