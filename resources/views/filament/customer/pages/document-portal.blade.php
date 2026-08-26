<x-filament-panels::page>
    @if (count($documents) > 0)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chantier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10 bg-white dark:bg-gray-900">
                    @foreach ($documents as $doc)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    @switch($doc['type'])
                                        @case('Devis')
                                            <x-heroicon-o-document-text class="w-4 h-4 text-primary-500" />
                                            @break
                                        @case('Commande')
                                            <x-heroicon-o-shopping-bag class="w-4 h-4 text-info-500" />
                                            @break
                                        @case('Bon de livraison')
                                            <x-heroicon-o-truck class="w-4 h-4 text-warning-500" />
                                            @break
                                        @case('Facture')
                                            <x-heroicon-o-banknotes class="w-4 h-4 text-success-500" />
                                            @break
                                        @case('Avoir')
                                            <x-heroicon-o-arrow-uturn-left class="w-4 h-4 text-gray-500" />
                                            @break
                                    @endswitch
                                    {{ $doc['type'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">
                                {{ $doc['reference'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $doc['chantier'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $doc['date']->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-md bg-{{ $doc['status_color'] }}-50 px-2 py-1 text-xs font-medium text-{{ $doc['status_color'] }}-700 ring-1 ring-inset ring-{{ $doc['status_color'] }}-600/20 dark:bg-{{ $doc['status_color'] }}-500/10 dark:text-{{ $doc['status_color'] }}-400 dark:ring-{{ $doc['status_color'] }}-500/30">
                                    {{ $doc['status'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ $doc['download_url'] }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                                    <x-heroicon-o-arrow-right-circle class="w-4 h-4" />
                                    Voir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-16">
            <x-heroicon-o-folder class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun document disponible.</p>
        </div>
    @endif
</x-filament-panels::page>
