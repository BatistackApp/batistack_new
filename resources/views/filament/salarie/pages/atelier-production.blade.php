<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Tabs -->
        <div class="flex space-x-4 border-b border-gray-200 dark:border-white/10 pb-4">
            <button 
                wire:click="$set('activeTab', 'todo')" 
                class="px-6 py-3 rounded-lg font-bold transition {{ $activeTab === 'todo' ? 'bg-primary-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                À faire / En cours
            </button>
            <button 
                wire:click="$set('activeTab', 'history')" 
                class="px-6 py-3 rounded-lg font-bold transition {{ $activeTab === 'history' ? 'bg-primary-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                Historique Terminé
            </button>
        </div>

        @if($activeTab === 'todo')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($this->todoOrders as $order)
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-200 dark:border-white/10 overflow-hidden flex flex-col">
                        <!-- Header -->
                        <div class="p-6 flex justify-between items-start border-b border-gray-100 dark:border-white/5">
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white">
                                    {{ $order->reference }}
                                </h3>
                                <p class="text-lg text-gray-500 mt-1">
                                    {{ $order->item->name ?? 'Article Inconnu' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="px-4 py-2 rounded-full text-sm font-bold shadow-sm 
                                    {{ $order->status === \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' }}">
                                    {{ $order->status->getLabel() }}
                                </span>
                                <div class="mt-2 text-3xl font-black text-primary-600">
                                    x {{ floatval($order->quantity_planned) }}
                                </div>
                            </div>
                        </div>

                        <!-- Nomenclature / Recette -->
                        <div class="p-6 flex-grow bg-gray-50 dark:bg-gray-800/50">
                            <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center justify-between">
                                <span>📋 Nomenclature (Recette)</span>
                                @if($order->item->hasMedia('docs'))
                                    <a href="{{ $order->item->getFirstMediaUrl('docs') }}" target="_blank" class="text-sm px-3 py-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded shadow-sm inline-flex items-center gap-1">
                                        <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4" />
                                        Voir PDF
                                    </a>
                                @endif
                            </h4>
                            <ul class="space-y-2">
                                @forelse($order->requirements as $req)
                                    <li class="flex justify-between items-center bg-white dark:bg-gray-900 p-3 rounded border border-gray-200 dark:border-white/5">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $req->item->name }}</span>
                                        <span class="text-gray-600 dark:text-gray-400 font-bold">{{ floatval($req->quantity_required) }} <span class="text-sm font-normal">unités</span></span>
                                    </li>
                                @empty
                                    <li class="text-gray-500 italic">Aucun composant requis.</li>
                                @endforelse
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="p-6 bg-white dark:bg-gray-900">
                            @if($order->status === \App\Enums\Gpao\ManufacturingStatus::PLANNED)
                                <button 
                                    wire:click="startTracking({{ $order->id }})" 
                                    class="w-full py-5 bg-blue-600 hover:bg-blue-500 text-white text-xl font-black rounded-xl shadow-lg transition transform active:scale-95 flex justify-center items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-play" class="h-8 w-8" />
                                    DÉMARRER LE POINTAGE
                                </button>
                            @elseif($order->status === \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS)
                                @if($this->hasActiveTracking($order->id))
                                    <button 
                                        wire:click="stopTracking({{ $order->id }})" 
                                        class="w-full mb-3 py-4 bg-orange-500 hover:bg-orange-400 text-white text-lg font-bold rounded-xl shadow transition flex justify-center items-center gap-2"
                                    >
                                        <x-filament::icon icon="heroicon-o-pause" class="h-6 w-6" />
                                        METTRE EN PAUSE (Arrêter Pointage)
                                    </button>
                                @else
                                    <button 
                                        wire:click="startTracking({{ $order->id }})" 
                                        class="w-full mb-3 py-4 bg-blue-500 hover:bg-blue-400 text-white text-lg font-bold rounded-xl shadow transition flex justify-center items-center gap-2"
                                    >
                                        <x-filament::icon icon="heroicon-o-play" class="h-6 w-6" />
                                        REPRENDRE LE POINTAGE
                                    </button>
                                @endif
                                <button 
                                    wire:click="finishOrder({{ $order->id }})" 
                                    class="w-full py-5 bg-green-600 hover:bg-green-500 text-white text-xl font-black rounded-xl shadow-lg transition transform active:scale-95 flex justify-center items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8" />
                                    TERMINER LA PRODUCTION
                                </button>
                            @endif
                            <button 
                                wire:click="downloadPdf({{ $order->id }})" 
                                class="w-full mt-3 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 rounded-xl shadow transition flex justify-center items-center gap-2 font-bold"
                            >
                                <x-filament::icon icon="heroicon-o-qr-code" class="h-6 w-6" />
                                TÉLÉCHARGER L'ÉTIQUETTE / OF
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                        <x-filament::icon icon="heroicon-o-inbox" class="h-16 w-16 mx-auto mb-4 text-gray-400" />
                        <h3 class="text-xl font-bold">Aucun Ordre de Fabrication</h3>
                        <p>Tout est à jour !</p>
                    </div>
                @endforelse
            </div>
        @else
            <!-- Historique -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 opacity-75">
                @forelse($this->historyOrders as $order)
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl shadow border border-gray-200 dark:border-white/10 p-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 line-through">
                                {{ $order->reference }}
                            </h3>
                            <p class="text-gray-500">
                                {{ $order->item->name ?? 'Article Inconnu' }} - Terminée le {{ $order->updated_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div>
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ $order->status->getLabel() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-gray-500">
                        <p>Aucun historique disponible.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
