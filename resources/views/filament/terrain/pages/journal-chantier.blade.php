<x-filament-panels::page>
    <div x-data="journalApp()" x-init="initApp" class="space-y-6">

        <!-- Status Bar -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
                <span class="text-sm text-gray-500">Les entrées sont conservées hors-ligne et synchronisées ensuite.</span>
            </div>
            <div>
                <button x-show="syncQueue.length > 0 && isOnline" @click="syncData" type="button" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    Synchroniser (<span x-text="syncQueue.length"></span>)
                </button>
            </div>
        </div>

        <!-- Selectors -->
        <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chantier</label>
                    <select x-model="selectedChantierId" @change="loadLogs" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600">
                        <option value="">-- Sélectionner un chantier --</option>
                        <template x-for="chantier in chantiers" :key="chantier.id">
                            <option :value="chantier.id" x-text="chantier.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                    <input type="date" x-model="selectedDate" @change="loadLogs" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!selectedChantierId" class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <x-heroicon-o-notebook class="w-12 h-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Journal de Chantier</h3>
            <p class="text-gray-500 text-sm mt-1">Sélectionnez un chantier et une date pour consulter ou ajouter des entrées.</p>
        </div>

        <!-- Add Entry Form -->
        <div x-show="selectedChantierId" class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Nouvelle entrée</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contenu</label>
                    <textarea x-model="newEntry.content" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600" placeholder="Description des travaux, observations..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condition météo</label>
                        <select x-model="newEntry.weather_condition" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600">
                            <option value="">-- Non renseigné --</option>
                            <option value="soleil">Soleil</option>
                            <option value="nuageux">Nuageux</option>
                            <option value="pluie">Pluie</option>
                            <option value="pluie_fort">Pluie forte</option>
                            <option value="neige">Neige</option>
                            <option value="brouillard">Brouillard</option>
                            <option value="vent">Vent</option>
                            <option value="orage">Orage</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="newEntry.incident_reported" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Incident signalé</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button @click="addEntry" type="button" :disabled="!newEntry.content.trim()" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Ajouter l'entrée
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing Logs -->
        <div x-show="selectedChantierId && logs.length > 0" class="space-y-3">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Entrées du <span x-text="formatDate(selectedDate)"></span></h3>

            <template x-for="log in logs" :key="log.id || log.client_key">
                <div class="p-4 rounded-lg bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500" x-text="log.created_at ? new Date(log.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : 'Hors-ligne'"></span>
                            <span x-show="log.incident_reported" class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-900/30 dark:text-red-200">Incident</span>
                            <span x-show="log.synced === false" class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-200">En attente de sync</span>
                        </div>
                        <span x-show="log.weather_condition" class="text-xs text-gray-500" x-text="weatherLabel(log.weather_condition)"></span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="log.content"></p>
                </div>
            </template>
        </div>

        <!-- No Logs State -->
        <div x-show="selectedChantierId && logs.length === 0 && !isLoading" class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <x-heroicon-o-document-text class="w-10 h-10 text-gray-400 mb-3" />
            <p class="text-gray-500 text-sm">Aucune entrée pour cette date.</p>
        </div>

        <!-- Loading -->
        <div x-show="isLoading" class="flex justify-center p-8">
            <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

    </div>

    <script src="https://unpkg.com/dexie@4.0.1/dist/dexie.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('journalApp', () => ({
                db: null,
                isOnline: navigator.onLine,
                chantiers: [],
                logs: [],
                syncQueue: [],
                selectedChantierId: null,
                selectedDate: new Date().toISOString().split('T')[0],
                isLoading: false,
                newEntry: {
                    content: '',
                    weather_condition: '',
                    incident_reported: false,
                },

                async initApp() {
                    this.db = new Dexie("batistack_journal_db");
                    this.db.version(1).stores({
                        logs: 'id, chantier_id, date, client_key, synced',
                        sync_queue: '++id, type, payload'
                    });

                    window.addEventListener('online', () => { this.isOnline = true; this.syncData(); });
                    window.addEventListener('offline', () => { this.isOnline = false; });

                    await this.loadChantiers();

                    if (this.selectedChantierId) {
                        await this.loadLogs();
                    }
                },

                async loadChantiers() {
                    try {
                        const response = await fetch('/api/journal/chantiers', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        this.chantiers = data.data || [];
                    } catch (error) {
                        console.error('Failed to fetch chantiers', error);
                    }
                },

                async loadLogs() {
                    if (!this.selectedChantierId) return;
                    this.isLoading = true;

                    try {
                        // Load from local DB first
                        const localLogs = await this.db.logs
                            .where('chantier_id').equals(parseInt(this.selectedChantierId))
                            .and(log => log.date === this.selectedDate)
                            .toArray();

                        // Mark local (unsynced) logs
                        this.logs = localLogs.map(log => ({
                            ...log,
                            synced: log.id && !String(log.id).startsWith('local_')
                        }));

                        // Fetch from server if online
                        if (this.isOnline) {
                            const response = await fetch(`/api/journal/logs?chantier_id=${this.selectedChantierId}&date=${this.selectedDate}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await response.json();
                            const serverLogs = data.data || [];

                            // Update local cache
                            await this.db.logs.where('chantier_id').equals(parseInt(this.selectedChantierId))
                                .and(log => log.date === this.selectedDate)
                                .delete();

                            for (const log of serverLogs) {
                                await this.db.logs.put({
                                    id: log.id,
                                    chantier_id: log.chantier_id,
                                    date: log.date,
                                    content: log.content,
                                    weather_condition: log.weather_condition,
                                    incident_reported: log.incident_reported,
                                    created_at: log.created_at,
                                    synced: true,
                                });
                            }

                            this.logs = serverLogs.map(log => ({ ...log, synced: true }));
                        }
                    } catch (error) {
                        console.error('Failed to load logs', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async addEntry() {
                    if (!this.newEntry.content.trim() || !this.selectedChantierId) return;

                    const clientKey = crypto.randomUUID ? crypto.randomUUID() : 'ck-' + Date.now();
                    const entry = {
                        id: 'local_' + clientKey,
                        chantier_id: parseInt(this.selectedChantierId),
                        date: this.selectedDate,
                        content: this.newEntry.content.trim(),
                        weather_condition: this.newEntry.weather_condition || null,
                        incident_reported: this.newEntry.incident_reported,
                        created_at: new Date().toISOString(),
                        synced: false,
                    };

                    // Save to local DB
                    await this.db.logs.put(entry);

                    // Queue for sync
                    await this.queueOperation('CREATE_LOG', {
                        chantier_id: entry.chantier_id,
                        date: entry.date,
                        content: entry.content,
                        weather_condition: entry.weather_condition,
                        incident_reported: entry.incident_reported,
                        client_key: clientKey,
                    });

                    // Reset form
                    this.newEntry.content = '';
                    this.newEntry.weather_condition = '';
                    this.newEntry.incident_reported = false;

                    // Reload
                    await this.loadLogs();
                },

                async queueOperation(type, payload) {
                    await this.db.sync_queue.add({ type, payload });
                    this.syncQueue = await this.db.sync_queue.toArray();
                    if (this.isOnline) {
                        this.syncData();
                    }
                },

                async syncData() {
                    if (this.syncQueue.length === 0 || !this.isOnline) return;
                    try {
                        const response = await fetch('/api/journal/sync', {
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
                            this.syncQueue = [];
                            await this.loadLogs();
                        }
                    } catch (error) {
                        console.error('Sync failed', error);
                    }
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const [y, m, d] = dateStr.split('-');
                    return `${d}/${m}/${y}`;
                },

                weatherLabel(condition) {
                    const labels = {
                        'soleil': '☀️ Soleil',
                        'nuageux': '☁️ Nuageux',
                        'pluie': '🌧️ Pluie',
                        'pluie_fort': '🌧️ Pluie forte',
                        'neige': '❄️ Neige',
                        'brouillard': '🌫️ Brouillard',
                        'vent': '💨 Vent',
                        'orage': '⛈️ Orage',
                    };
                    return labels[condition] || condition;
                },
            }));
        });
    </script>
</x-filament-panels::page>
