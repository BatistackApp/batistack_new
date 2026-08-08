<x-filament-panels::page>
    <div class="mb-6 space-y-4">
        {{ $this->form }}
    </div>

    @php
        $totalCo2Kg = $this->getTotalCo2();
        $totalCo2Tons = number_format($totalCo2Kg / 1000, 2);
        
        $byMonth = $this->getCo2ByMonth();
        $byChantier = $this->getCo2ByChantier();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10 flex flex-col justify-center items-center text-center">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Émissions Totales</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $totalCo2Tons }} Tonnes</p>
            <p class="text-xs text-gray-400 mt-1">Équivalent CO2 ({{ number_format($totalCo2Kg, 0, ',', ' ') }} kg)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Répartition par mois -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Émissions par mois</h3>
            @if(empty($byMonth))
                <p class="text-sm text-gray-500">Aucune donnée pour cette période.</p>
            @else
                <div class="space-y-3">
                    @foreach($byMonth as $month => $kg)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</span>
                            <span class="text-sm text-gray-500">{{ number_format($kg / 1000, 2) }} T</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            @php
                                $percentage = $totalCo2Kg > 0 ? ($kg / $totalCo2Kg) * 100 : 0;
                            @endphp
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Répartition par chantier -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Répartition par Chantier</h3>
            @if(empty($byChantier))
                <p class="text-sm text-gray-500">Aucune donnée affectée aux chantiers pour cette période.</p>
            @else
                <div class="space-y-4">
                    @foreach($byChantier as $chantier)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium truncate pr-4">{{ $chantier['name'] }}</span>
                            <span class="text-sm text-gray-500 shrink-0">{{ number_format($chantier['total_kg'] / 1000, 2) }} T</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            @php
                                $percentage = $totalCo2Kg > 0 ? ($chantier['total_kg'] / $totalCo2Kg) * 100 : 0;
                            @endphp
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
