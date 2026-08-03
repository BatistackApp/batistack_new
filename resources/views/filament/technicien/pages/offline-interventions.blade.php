<x-filament-panels::page>
    <div x-data="offlineApp()" x-init="initApp" class="space-y-6">
        
        <!-- Status Bar -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
            </div>
            <div>
                <button x-show="syncQueue.length > 0 && isOnline" @click="syncData" type="button" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Synchroniser (<span x-text="syncQueue.length"></span>)
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="interventions.length === 0" style="display: none;" class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <x-heroicon-o-calendar class="w-12 h-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucune intervention</h3>
            <p class="text-gray-500 text-sm mt-1">Vous n'avez aucune intervention assignée ou la liste est en cours de chargement.</p>
        </div>

        <!-- Intervention List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="intervention in interventions" :key="intervention.id">
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="intervention.reference"></h3>
                            <p class="text-sm text-gray-500" x-text="intervention.chantier?.name || 'Sans Chantier'"></p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" 
                              :class="{
                                'bg-yellow-100 text-yellow-800': intervention.status === 'PLANIFIEE',
                                'bg-blue-100 text-blue-800': intervention.status === 'EN_COURS',
                                'bg-green-100 text-green-800': intervention.status === 'TERMINEE'
                              }"
                              x-text="intervention.status">
                        </span>
                    </div>

                    <div class="space-y-2 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-m-user class="w-4 h-4"/>
                            <span x-text="intervention.third_party?.name"></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-m-map-pin class="w-4 h-4"/>
                            <span x-text="intervention.chantier?.address + ', ' + intervention.chantier?.city"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button @click="setStatus(intervention, 'EN_COURS')" x-show="intervention.status === 'PLANIFIEE'" class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Démarrer l'intervention
                        </button>
                        
                        <div x-show="intervention.status === 'EN_COURS'" class="space-y-3">
                            <button @click="openMaterialModal(intervention)" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                                Ajouter Matériel
                            </button>
                            <button @click="capturePhoto(intervention)" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                                Prendre une Photo
                            </button>
                            <button @click="captureGPS(intervention)" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                                Enregistrer Position GPS
                            </button>
                            <button @click="setStatus(intervention, 'TERMINEE')" class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Terminer l'intervention
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Material Modal -->
        <div x-show="isMaterialModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
            <div class="w-full max-w-md p-6 bg-white rounded-xl dark:bg-gray-800 shadow-xl m-4" @click.away="isMaterialModalOpen = false">
                <h3 class="text-lg font-bold mb-4">Ajouter du matériel</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom du matériel</label>
                        <input type="text" x-model="newMaterial.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantité</label>
                        <input type="number" x-model="newMaterial.quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="isMaterialModalOpen = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
                        <button @click="saveMaterial" type="button" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script imports -->
    <script src="https://unpkg.com/dexie@4.0.1/dist/dexie.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('offlineApp', () => ({
                db: null,
                isOnline: navigator.onLine,
                interventions: [],
                syncQueue: [],
                isMaterialModalOpen: false,
                currentIntervention: null,
                newMaterial: { name: '', quantity: 1, price: 0 },

                async initApp() {
                    // Initialize Dexie
                    this.db = new Dexie("batistack_offline_db");
                    this.db.version(1).stores({
                        interventions: 'id, reference, status, third_party_id, chantier_id',
                        sync_queue: '++id, type, payload'
                    });

                    // Network listeners
                    window.addEventListener('online', () => {
                        this.isOnline = true;
                        this.syncData();
                    });
                    window.addEventListener('offline', () => {
                        this.isOnline = false;
                    });

                    await this.loadLocalData();

                    if (this.isOnline) {
                        await this.fetchFromServer();
                    }
                },

                async loadLocalData() {
                    this.interventions = await this.db.interventions.toArray();
                    this.syncQueue = await this.db.sync_queue.toArray();
                },

                async fetchFromServer() {
                    try {
                        const response = await fetch('/api/technicien/interventions', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        
                        if (data.data) {
                            // Update local DB
                            await this.db.interventions.clear();
                            await this.db.interventions.bulkAdd(data.data);
                            this.interventions = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to fetch from server', error);
                    }
                },

                async queueOperation(type, payload) {
                    await this.db.sync_queue.add({ type, payload });
                    await this.loadLocalData();
                    
                    if (this.isOnline) {
                        this.syncData();
                    }
                },

                async syncData() {
                    if (this.syncQueue.length === 0 || !this.isOnline) return;

                    try {
                        const response = await fetch('/api/technicien/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({ operations: this.syncQueue })
                        });

                        const result = await response.json();
                        
                        if (result.success) {
                            await this.db.sync_queue.clear();
                            await this.loadLocalData();
                            // Refresh fresh state
                            await this.fetchFromServer();
                        }
                    } catch (error) {
                        console.error('Sync failed', error);
                    }
                },

                async setStatus(intervention, status) {
                    intervention.status = status;
                    if(status === 'TERMINEE') {
                        intervention.completed_at = new Date().toISOString();
                    }
                    await this.db.interventions.put(JSON.parse(JSON.stringify(intervention)));
                    await this.queueOperation('UPDATE_STATUS', { 
                        intervention_id: intervention.id, 
                        status: status,
                        completed_at: intervention.completed_at || null
                    });
                },

                openMaterialModal(intervention) {
                    this.currentIntervention = intervention;
                    this.newMaterial = { name: '', quantity: 1, price: 0 };
                    this.isMaterialModalOpen = true;
                },

                async saveMaterial() {
                    if (!this.currentIntervention || !this.newMaterial.name) return;
                    
                    await this.queueOperation('ADD_MATERIAL', {
                        intervention_id: this.currentIntervention.id,
                        name: this.newMaterial.name,
                        quantity: this.newMaterial.quantity,
                        price: this.newMaterial.price
                    });
                    
                    this.isMaterialModalOpen = false;
                },

                captureGPS(intervention) {
                    if (!navigator.geolocation) {
                        alert("La géolocalisation n'est pas supportée par votre navigateur.");
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(async (position) => {
                        await this.queueOperation('UPDATE_GPS', {
                            intervention_id: intervention.id,
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        });
                        alert('Position GPS enregistrée avec succès.');
                    }, () => {
                        alert("Impossible de récupérer la position.");
                    });
                },

                capturePhoto(intervention) {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.capture = 'environment';
                    
                    input.onchange = (e) => {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onloadend = async () => {
                                await this.queueOperation('UPLOAD_PHOTO', {
                                    intervention_id: intervention.id,
                                    image: reader.result
                                });
                                alert('Photo enregistrée et prête à être synchronisée.');
                            };
                            reader.readAsDataURL(file);
                        }
                    };
                    
                    input.click();
                }
            }));
        });
    </script>
</x-filament-panels::page>
