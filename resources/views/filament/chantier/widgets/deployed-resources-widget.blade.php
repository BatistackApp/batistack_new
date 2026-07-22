<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Ressources Déployées (Propre & Location)
        </x-slot>

        @php
            $resources = $this->getResources();
        @endphp

        @if(count($resources) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Désignation</th>
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Origine</th>
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Fournisseur</th>
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Dates</th>
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Coût</th>
                            <th class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($resources as $resource)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $resource['name'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <x-filament::badge :color="$resource['type'] === 'Location' ? 'warning' : 'success'">
                                        {{ $resource['type'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $resource['supplier'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $resource['start_date'] }} - {{ $resource['end_date'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $resource['cost'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $resource['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex items-center justify-center p-4 text-sm text-gray-500 dark:text-gray-400">
                Aucune ressource matérielle déployée sur ce chantier.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
