<x-filament-panels::page>
    <div x-data="scanApp()" x-init="init()">

        {{-- Status Bar --}}
        <div class="flex items-center justify-between p-4 rounded-xl bg-white shadow dark:bg-gray-800 mb-4">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500" x-text="todayCount + ' scan(s) aujourd\'hui'"></span>
            </div>
        </div>

        {{-- Scan Result/Error --}}
        <div x-show="scanResult" x-transition
            class="p-4 mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300"
            x-text="scanResult"></div>

        <div x-show="scanError" x-transition
            class="p-4 mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300"
            x-text="scanError"></div>

        {{-- Equipment Info Card --}}
        <div x-show="trackableInfo" x-transition class="p-4 mb-4 rounded-xl bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-700">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                    :class="trackableType === '{{ \App\Models\Immobilisation\FixedAsset::class }}' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600'">
                    <svg x-show="trackableType === '{{ \App\Models\Immobilisation\FixedAsset::class }}'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <svg x-show="trackableType !== '{{ \App\Models\Immobilisation\FixedAsset::class }}'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white" x-text="trackableInfo"></p>
                    <p class="text-sm text-gray-500" x-show="currentChantier" x-text="'Actuellement sur : ' + currentChantier"></p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-4 flex gap-3">
                <button @click="actionType = 'check_in'" type="button"
                    class="touch-target flex-1 px-4 py-3 text-sm font-medium rounded-xl transition-all active:scale-[0.97]"
                    :class="actionType === 'check_in' ? 'bg-green-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                    📥 Arrivée
                </button>
                <button @click="actionType = 'check_out'" type="button"
                    :disabled="!existingTrackingId"
                    class="touch-target flex-1 px-4 py-3 text-sm font-medium rounded-xl transition-all active:scale-[0.97] disabled:opacity-30"
                    :class="actionType === 'check_out' ? 'bg-amber-500 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                    📤 Départ
                </button>
            </div>

            {{-- Submit --}}
            <div class="mt-4">
                <button @click="submitScan()" type="button"
                    :disabled="!actionType || !chantierId"
                    class="touch-target w-full px-6 py-3 text-base font-medium text-white rounded-xl transition-all active:scale-[0.98] shadow-md disabled:opacity-50"
                    :class="actionType === 'check_in' ? 'bg-green-600 hover:bg-green-700' : 'bg-amber-600 hover:bg-amber-700'">
                    <span x-text="actionType === 'check_in' ? 'Enregistrer l\'arrivée' : 'Enregistrer le départ'"></span>
                </button>
            </div>
        </div>

        {{-- Today's Presences --}}
        <div class="mt-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Présences du jour</h3>

            <div x-show="presences.length === 0" class="p-6 text-center bg-white rounded-xl shadow-sm dark:bg-gray-800">
                <p class="text-gray-500 text-sm">Aucune présence enregistrée aujourd'hui.</p>
            </div>

            <div class="space-y-2">
                <template x-for="p in presences" :key="p.id">
                    <div class="flex items-center justify-between p-3 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold"
                                :class="p.is_out ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700'"
                                x-text="p.is_out ? '✓' : '●'"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="p.label"></p>
                                <p class="text-xs text-gray-500" x-text="p.type_label + ' — ' + p.chantier_name"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium" :class="p.is_out ? 'text-gray-400' : 'text-green-600'"
                                x-text="p.is_out ? 'Sorti' : 'Présent'"></p>
                            <p class="text-xs text-gray-400" x-text="p.check_in_time"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('scanApp', () => ({
                isOnline: navigator.onLine,
                scanResult: @json($this->scanResult),
                scanError: @json($this->scanError),
                trackableInfo: null,
                trackableType: null,
                trackableId: null,
                actionType: null,
                existingTrackingId: null,
                currentChantier: null,
                chantierId: @json($this->data['chantier_id'] ?? null),
                notes: '',
                presences: [],
                todayCount: 0,

                init() {
                    window.addEventListener('online', () => { this.isOnline = true; });
                    window.addEventListener('offline', () => { this.isOnline = false; });

                    this.loadPresences();

                    // Watch for Livewire updates
                    this.$wire.$on('scanUpdated', () => {
                        this.loadPresences();
                        this.resetForm();
                    });
                },

                async loadPresences() {
                    try {
                        const response = await fetch('/api/chantier-equipment/presence' + (this.chantierId ? '?chantier_id=' + this.chantierId : ''), {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        this.presences = data.data || [];
                        this.todayCount = this.presences.length;
                    } catch (e) {
                        console.log('Failed to load presences');
                    }
                },

                resetForm() {
                    this.trackableInfo = null;
                    this.trackableType = null;
                    this.trackableId = null;
                    this.actionType = null;
                    this.existingTrackingId = null;
                    this.currentChantier = null;
                    this.scanResult = null;
                    this.scanError = null;
                },

                submitScan() {
                    // Trigger Livewire form submission
                    this.$wire.call('submit');
                    setTimeout(() => this.loadPresences(), 1000);
                },
            }));
        });
    </script>
</x-filament-panels::page>
