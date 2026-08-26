<x-filament-panels::page>
    <div class="space-y-6">

        @if(empty($chantiers))
            <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <x-heroicon-o-cube class="w-12 h-12 text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucune maquette BIM</h3>
                <p class="text-gray-500 text-sm mt-1">Aucun modèle 3D n'est disponible pour vos chantiers actuels.</p>
            </div>
        @else
            <!-- Chantier selector -->
            <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sélectionner un chantier</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($chantiers as $chantier)
                        <button
                            type="button"
                            wire:click="mount"
                            class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors
                                {{ count($chantier['bim_models'] ?? []) > 0
                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                    : 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800' }}"
                            {{ count($chantier['bim_models'] ?? []) === 0 ? 'disabled' : '' }}
                        >
                            {{ $chantier['name'] }}
                            <span class="ml-1 text-xs opacity-60">({{ count($chantier['bim_models'] ?? []) }})</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Models grid -->
            @php
                $allModels = collect($models);
            @endphp

            @if($allModels->isEmpty())
                <div class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-gray-500 text-sm">Aucune maquette disponible.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($allModels as $model)
                        <div class="p-5 rounded-xl bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 {{ $selectedModelId == $model['id'] ? 'ring-2 ring-emerald-500' : '' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $model['name'] }}</h3>
                                    <p class="text-xs text-gray-500">{{ $model['chantier_name'] }}</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded dark:bg-blue-900/30 dark:text-blue-200">
                                    {{ strtoupper($model['format'] ?? 'IFC') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                                @if($model['version'])
                                    <span>v{{ $model['version'] }}</span>
                                    <span>·</span>
                                @endif
                                @if($model['file_size'])
                                    <span>{{ number_format($model['file_size'] / 1024 / 1024, 1, ',', ' ') }} Mo</span>
                                @endif
                            </div>

                            <!-- Thumbnail -->
                            @if($model['thumbnail_path'])
                                <div class="mb-3 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ Storage::disk('public')->url($model['thumbnail_path']) }}"
                                         alt="{{ $model['name'] }}"
                                         class="w-full h-32 object-cover" />
                                </div>
                            @else
                                <div class="mb-3 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center h-32">
                                    <x-heroicon-o-cube class="w-10 h-10 text-gray-400" />
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <a href="/bim-viewer-headless/{{ $model['id'] }}"
                                   target="_blank"
                                   class="flex-1 px-3 py-2 text-sm font-medium text-center text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                    Ouvrir le viewer
                                </a>
                                @if($model['file_path'])
                                    <a href="{{ Storage::disk('public')->url($model['file_path']) }}"
                                       download
                                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
