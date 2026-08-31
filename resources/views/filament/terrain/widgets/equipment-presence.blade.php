<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Matériel sur site</h3>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500" x-text="{{ count($presences) }} élément(s)"></span>
            @if($totalCost > 0)
                <span class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-200">
                    {{ number_format($totalCost, 0, ',', ' ') }} € aujourd'hui
                </span>
            @endif
        </div>
    </div>

    @if(empty($presences))
        <div class="p-6 text-center bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <p class="text-sm text-gray-500">Aucun matériel présent sur le chantier aujourd'hui.</p>
            <a href="{{ \App\Filament\Terrain\Pages\ScanChantierEquipmentPage::getUrl() }}"
                class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">
                Scanner du matériel →
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($presences as $presence)
                <div class="flex items-center gap-3 p-3 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold
                        {{ $presence['type_class'] === 'orange' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                        @if($presence['type_class'] === 'orange')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $presence['label'] }}</p>
                        <p class="text-xs text-gray-500">{{ $presence['type'] }} · Depuis {{ $presence['since'] }}</p>
                    </div>
                    @if($presence['cost'] > 0)
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                            {{ number_format($presence['cost'], 0, ',', ' ') }} €
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
