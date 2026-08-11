<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit.prevent="search" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" color="primary">
                Comparer les offres
            </x-filament::button>
        </form>
    </x-filament::card>

    @if(count($results) > 0)
        <x-filament::card class="mt-6">
            <h2 class="text-xl font-bold mb-4">Meilleures offres (triées par prix total)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="p-4 font-medium text-gray-900 dark:text-gray-100">Fournisseur</th>
                            <th class="p-4 font-medium text-gray-900 dark:text-gray-100">Jour</th>
                            <th class="p-4 font-medium text-gray-900 dark:text-gray-100">Semaine</th>
                            <th class="p-4 font-medium text-gray-900 dark:text-gray-100">Mois</th>
                            <th class="p-4 font-bold text-gray-900 dark:text-gray-100">Coût Total Estimé</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($results as $index => $result)
                            <tr class="{{ $index === 0 ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <td class="p-4 flex items-center gap-2">
                                    @if($index === 0)
                                        <x-heroicon-s-trophy class="w-5 h-5 text-yellow-500" />
                                    @endif
                                    {{ $result['supplier_name'] }}
                                </td>
                                <td class="p-4">{{ $result['daily_rate'] ? number_format($result['daily_rate'], 2) . ' €' : '-' }}</td>
                                <td class="p-4">{{ $result['weekly_rate'] ? number_format($result['weekly_rate'], 2) . ' €' : '-' }}</td>
                                <td class="p-4">{{ $result['monthly_rate'] ? number_format($result['monthly_rate'], 2) . ' €' : '-' }}</td>
                                <td class="p-4 font-bold text-primary-600 dark:text-primary-400">
                                    {{ number_format($result['total_cost'], 2) }} €
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    @elseif(!is_null($equipment_category))
        <x-filament::card class="mt-6">
            <p class="text-gray-500">Aucun tarif trouvé pour cette catégorie d'équipement.</p>
        </x-filament::card>
    @endif
</x-filament-panels::page>
