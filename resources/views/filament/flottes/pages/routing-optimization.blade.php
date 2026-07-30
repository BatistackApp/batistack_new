<x-filament-panels::page>
    <div class="space-y-6">
        
        <x-filament::section>
            <x-slot name="heading">
                Optimisation IA des affectations
            </x-slot>
            
            <x-slot name="description">
                Générez des suggestions d'affectation pour les véhicules disponibles vers les chantiers actifs, en minimisant la distance totale.
            </x-slot>
            
            <div class="flex items-center gap-4 mt-4">
                <x-filament::button 
                    wire:click="generateSuggestions"
                    icon="heroicon-o-sparkles"
                    color="primary"
                    :disabled="$isGenerating"
                >
                    <span wire:loading.remove wire:target="generateSuggestions">
                        Générer les suggestions
                    </span>
                    <span wire:loading wire:target="generateSuggestions">
                        Génération en cours...
                    </span>
                </x-filament::button>
            </div>
        </x-filament::section>

        @if(!empty($suggestions))
            <x-filament::section>
                <x-slot name="heading">
                    Suggestions ({{ count($suggestions) }})
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-left divide-y table-auto filament-tables-table">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="px-4 py-3 text-sm font-medium text-gray-500">Véhicule</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-500">Chantier Destination</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-500">Distance</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-500">Durée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y whitespace-nowrap">
                            @foreach($suggestions as $suggestion)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $suggestion['vehicle_name'] }}</td>
                                    <td class="px-4 py-3">{{ $suggestion['chantier_name'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">
                                            {{ $suggestion['distance_km'] }} km
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $suggestion['duration_mins'] }} min</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-4">
                    <x-filament::button 
                        wire:click="confirmAssignments"
                        icon="heroicon-o-check"
                        color="success"
                        size="lg"
                    >
                        Valider et Assigner
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
