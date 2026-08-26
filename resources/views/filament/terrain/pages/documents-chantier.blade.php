<x-filament-panels::page>
    @if($record)
        <div class="space-y-6">
            <!-- Chantier Info -->
            <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-sm font-mono text-gray-500">{{ $record->reference }}</span>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ match($record->status) {
                        'in_progress' => 'bg-amber-100 text-amber-800',
                        'waiting' => 'bg-blue-100 text-blue-800',
                        'finished' => 'bg-green-100 text-green-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">{{ $record->status->getLabel() }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $record->name }}</h2>
                <p class="text-sm text-gray-500">{{ $record->client?->name }} — {{ $record->city }}</p>
            </div>

            <!-- Documents List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $service = app(\App\Services\Chantiers\ChantierDocumentService::class);
                    $documents = [
                        ['key' => 'start_order', 'label' => 'Ordre de Service', 'description' => 'Fiche de lancement avec planning et ressources', 'icon' => 'heroicon-o-document-text', 'color' => 'primary'],
                        ['key' => 'rentability', 'label' => 'Rapport de Rentabilité', 'description' => 'Analyse financière et rentabilité du chantier', 'icon' => 'heroicon-o-chart-bar', 'color' => 'warning'],
                        ['key' => 'journal', 'label' => 'Journal Hebdomadaire', 'description' => 'Synthèse de la semaine en cours', 'icon' => 'heroicon-o-book-open', 'color' => 'info'],
                        ['key' => 'ppsps', 'label' => 'PPSPS', 'description' => 'Plan Particulier de Sécurité et de Protection de la Santé', 'icon' => 'heroicon-o-shield-check', 'color' => 'success'],
                        ['key' => 'pv', 'label' => 'PV de Réception', 'description' => 'Protocole de réception avec réserves', 'icon' => 'heroicon-o-document-check', 'color' => 'gray'],
                    ];
                @endphp

                @foreach($documents as $doc)
                    <div class="p-5 rounded-xl bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex flex-col">
                        <div class="flex items-start gap-3 mb-3">
                            <div class="p-2 rounded-lg bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900/20">
                                <x-dynamic-component :component="$doc['icon']" class="w-5 h-5 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $doc['label'] }}</h3>
                                <p class="text-xs text-gray-500">{{ $doc['description'] }}</p>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <button
                                wire:click="downloadDocument('{{ $doc['key'] }}')"
                                type="button"
                                class="w-full px-4 py-2 text-sm font-medium text-white bg-{{ $doc['color'] }}-600 rounded-lg hover:bg-{{ $doc['color'] }}-700 transition-colors"
                            >
                                Générer et télécharger
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(session('error'))
                <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col items-center justify-center p-12">
            <p class="text-gray-500">Aucun chantier sélectionné.</p>
        </div>
    @endif
</x-filament-panels::page>
