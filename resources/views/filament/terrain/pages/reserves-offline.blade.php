<x-filament-panels::page>
    <div x-data="reservesApp()" x-init="initApp()" class="space-y-6">

        <!-- Status Bar -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
                <span class="text-sm text-gray-500 hidden sm:inline">Les réserves sont sauvegardées localement.</span>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="syncQueue.length > 0" class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-200">
                    <span x-text="syncQueue.length"></span> en attente
                </span>
                <button x-show="syncQueue.length > 0 && isOnline" @click="syncData()" type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700">
                    Synchroniser
                </button>
            </div>
        </div>

        <!-- Tabs: Signal / List -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button @click="activeTab = 'signal'" type="button"
                :class="activeTab === 'signal' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 text-sm font-medium text-center border-b-2 transition-colors">
                Signaler une réserve
            </button>
            <button @click="activeTab = 'list'; loadReserves()" type="button"
                :class="activeTab === 'list' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 text-sm font-medium text-center border-b-2 transition-colors">
                Réserves existantes (<span x-text="reserves.length"></span>)
            </button>
        </div>

        <!-- Tab: Signal New Reserve -->
        <div x-show="activeTab === 'signal'" class="space-y-4">

            <!-- Select Chantier -->
            <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chantier concerné</label>
                <select x-model="form.chantier_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600">
                    <option value="">-- Sélectionner un chantier --</option>
                    <template x-for="chantier in chantiers" :key="chantier.id">
                        <option :value="chantier.id" x-text="chantier.name"></option>
                    </template>
                </select>
            </div>

            <!-- Form -->
            <div x-show="form.chantier_id" class="p-4 rounded-lg bg-white shadow space-y-4 dark:bg-gray-800">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objet de la réserve *</label>
                    <input type="text" x-model="form.title" required
                        placeholder="Ex: Fissure mur porteur"
                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea x-model="form.description" rows="3"
                        placeholder="Détails du défaut constaté..."
                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white resize-none"></textarea>
                </div>

                <!-- Severity Selector (Touch-optimized grid) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gravité *</label>
                    <div class="status-grid">
                        <label class="status-option" :class="{ 'selected': form.severity === 'info' }">
                            <input type="radio" name="severity" value="info" x-model="form.severity" />
                            <span class="badge-severity badge-low">ℹ️ Info</span>
                        </label>
                        <label class="status-option" :class="{ 'selected': form.severity === 'minor' }">
                            <input type="radio" name="severity" value="minor" x-model="form.severity" />
                            <span class="badge-severity badge-medium">⚠️ Mineur</span>
                        </label>
                        <label class="status-option" :class="{ 'selected': form.severity === 'major' }">
                            <input type="radio" name="severity" value="major" x-model="form.severity" />
                            <span class="badge-severity badge-high">🔶 Majeur</span>
                        </label>
                        <label class="status-option" :class="{ 'selected': form.severity === 'critical' }">
                            <input type="radio" name="severity" value="critical" x-model="form.severity" />
                            <span class="badge-severity badge-critical">🔴 Critique</span>
                        </label>
                    </div>
                </div>

                <!-- GPS Capture -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position GPS</label>
                    <button @click="captureGPS()" type="button"
                        class="touch-target w-full px-4 py-3 text-sm font-medium rounded-xl border-2 transition-colors"
                        :class="form.latitude ? 'bg-green-50 border-green-400 text-green-700 dark:bg-green-900/20 dark:border-green-600 dark:text-green-400' : 'bg-gray-50 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'">
                        <span x-text="form.latitude ? '✓ Position capturée (' + form.latitude.toFixed(5) + ', ' + form.longitude.toFixed(5) + ')' : '📍 Capturer ma position GPS'"></span>
                    </button>
                </div>

                <!-- Photo Capture (Touch-optimized) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photos du défaut</label>
                    <label for="reserve-photo"
                        class="touch-target flex items-center gap-3 px-4 py-3 text-sm font-medium text-amber-600 bg-amber-50 rounded-xl hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 cursor-pointer active:scale-[0.98] transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Capturer une photo
                    </label>
                    <input type="file" id="reserve-photo" accept="image/*" capture="environment"
                        class="hidden" @change="handlePhoto($event)" />
                    <!-- Photo Grid -->
                    <div x-show="form.photos.length > 0" class="photo-grid mt-3">
                        <template x-for="(photo, idx) in form.photos" :key="idx">
                            <div class="photo-item">
                                <img :src="photo" />
                                <button @click="form.photos.splice(idx, 1)" type="button"
                                    class="photo-remove">✕</button>
                            </div>
                        </template>
                    </div>
                    <p x-show="form.photos.length > 0" class="text-xs text-gray-400 mt-1" x-text="form.photos.length + ' photo(s) — compression auto'"></p>
                </div>

                <!-- Submit -->
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button @click="submitReserve()" type="button"
                        :disabled="!form.title.trim() || !form.chantier_id"
                        class="touch-target flex-1 px-4 py-3 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition-all shadow-md">
                        <span x-text="isOnline ? 'Envoyer la réserve' : 'Sauvegarder localement'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab: Existing Reserves -->
        <div x-show="activeTab === 'list'" class="space-y-3">
            <!-- Filter -->
            <div class="p-3 rounded-lg bg-white shadow dark:bg-gray-800">
                <select x-model="filterStatus" @change="loadReserves()"
                    class="w-full rounded-xl border-2 border-gray-200 text-sm shadow-sm focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="open">Ouverte</option>
                    <option value="in_progress">En cours</option>
                    <option value="resolved">Résolue</option>
                    <option value="lifted">Levée</option>
                </select>
            </div>

            <!-- Loading -->
            <div x-show="isLoading" class="flex justify-center p-8">
                <div class="w-8 h-8 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <!-- Empty State -->
            <div x-show="!isLoading && reserves.length === 0" class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <x-heroicon-o-check-circle class="w-10 h-10 text-gray-400 mb-3" />
                <p class="text-gray-500 text-sm">Aucune réserve pour ce chantier.</p>
            </div>

            <!-- Reserve Cards -->
            <template x-for="reserve in reserves" :key="reserve.id || reserve.client_key">
                <div class="p-4 rounded-lg bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-mono text-gray-500" x-text="reserve.reference || 'Brouillon'"></span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200': reserve.severity === 'critical',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200': reserve.severity === 'major',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200': reserve.severity === 'minor',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-200': reserve.severity === 'info',
                                }" x-text="severityLabel(reserve.severity)"></span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-red-100 text-red-800': reserve.status === 'open',
                                    'bg-yellow-100 text-yellow-800': reserve.status === 'in_progress',
                                    'bg-blue-100 text-blue-800': reserve.status === 'resolved',
                                    'bg-green-100 text-green-800': reserve.status === 'lifted',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200': !reserve.synced,
                                }" x-text="reserve.synced === false ? 'En attente' : statusLabel(reserve.status)"></span>
                        </div>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="reserve.title"></h4>
                    <p x-show="reserve.description" class="text-xs text-gray-500 mt-1 line-clamp-2" x-text="reserve.description"></p>
                    <div class="flex justify-between items-center mt-2 text-xs text-gray-400">
                        <span x-text="reserve.chantier_name || ''"></span>
                        <span x-text="reserve.created_at ? new Date(reserve.created_at).toLocaleDateString('fr-FR') : ''"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Reconnect Toast -->
        <div x-show="showSyncToast" x-transition
            class="fixed bottom-4 right-4 z-50 px-4 py-3 bg-green-600 text-white rounded-lg shadow-lg flex items-center gap-2">
            <x-heroicon-o-check-circle class="w-5 h-5" />
            <span x-text="syncToastMessage"></span>
        </div>
    </div>

    <script src="https://unpkg.com/dexie@4.0.1/dist/dexie.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reservesApp', () => ({
                db: null,
                isOnline: navigator.onLine,
                activeTab: 'signal',
                chantiers: [],
                reserves: [],
                syncQueue: [],
                filterStatus: '',
                isLoading: false,
                showSyncToast: false,
                syncToastMessage: '',
                form: {
                    chantier_id: '',
                    title: '',
                    description: '',
                    severity: 'major',
                    latitude: null,
                    longitude: null,
                    photos: [],
                },

                async initApp() {
                    this.db = new Dexie('batistack_reserves_db');
                    this.db.version(1).stores({
                        reserves: 'id, chantier_id, client_key, synced',
                        chantiers_cache: 'id',
                        sync_queue: '++id, type, payload'
                    });

                    window.addEventListener('online', () => {
                        this.isOnline = true;
                        this.syncData();
                    });
                    window.addEventListener('offline', () => { this.isOnline = false; });

                    await this.loadChantiers();
                },

                async loadChantiers() {
                    // Try cache first
                    const cached = await this.db.chantiers_cache.toArray();
                    if (cached.length > 0) {
                        this.chantiers = cached;
                    }

                    if (this.isOnline) {
                        try {
                            const response = await fetch('/api/reserves/chantiers', {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await response.json();
                            this.chantiers = data.data || [];
                            // Update cache
                            await this.db.chantiers_cache.clear();
                            for (const c of this.chantiers) {
                                await this.db.chantiers_cache.put(c);
                            }
                        } catch (error) {
                            console.error('Failed to fetch chantiers', error);
                        }
                    }
                },

                async loadReserves() {
                    this.isLoading = true;
                    try {
                        if (this.isOnline && this.form.chantier_id) {
                            const response = await fetch(`/api/reserves/list?chantier_id=${this.form.chantier_id}${this.filterStatus ? '&status=' + this.filterStatus : ''}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await response.json();
                            this.reserves = data.data || [];

                            // Cache in IndexedDB
                            await this.db.reserves
                                .where('chantier_id').equals(parseInt(this.form.chantier_id))
                                .delete();
                            for (const r of this.reserves) {
                                await this.db.reserves.put({ ...r, synced: true });
                            }
                        } else if (this.form.chantier_id) {
                            // Offline: load from cache
                            this.reserves = await this.db.reserves
                                .where('chantier_id').equals(parseInt(this.form.chantier_id))
                                .toArray();
                        }
                    } catch (error) {
                        console.error('Failed to load reserves', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                captureGPS() {
                    if (!navigator.geolocation) {
                        alert('La géolocalisation n\'est pas supportée par votre navigateur.');
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.form.latitude = pos.coords.latitude;
                            this.form.longitude = pos.coords.longitude;
                        },
                        (err) => {
                            console.error('GPS error', err);
                            alert('Impossible de capturer la position. Vérifiez les permissions.');
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },

                handlePhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            let { width, height } = img;
                            if (width > 1024) {
                                height = Math.round((height * 1024) / width);
                                width = 1024;
                            }
                            canvas.width = width;
                            canvas.height = height;
                            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                            this.form.photos.push(canvas.toDataURL('image/jpeg', 0.8));
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    event.target.value = '';
                },

                async submitReserve() {
                    if (!this.form.title.trim() || !this.form.chantier_id) return;

                    const clientKey = crypto.randomUUID ? crypto.randomUUID() : 'rk-' + Date.now();

                    const reserve = {
                        id: 'local_' + clientKey,
                        chantier_id: parseInt(this.form.chantier_id),
                        chantier_name: this.chantiers.find(c => c.id == this.form.chantier_id)?.name || '',
                        title: this.form.title.trim(),
                        description: this.form.description.trim() || null,
                        severity: this.form.severity,
                        status: 'open',
                        latitude: this.form.latitude,
                        longitude: this.form.longitude,
                        photos: this.form.photos,
                        created_at: new Date().toISOString(),
                        synced: false,
                    };

                    await this.db.reserves.put(reserve);

                    await this.queueOperation('CREATE_RESERVE', {
                        chantier_id: reserve.chantier_id,
                        title: reserve.title,
                        description: reserve.description,
                        severity: reserve.severity,
                        latitude: reserve.latitude,
                        longitude: reserve.longitude,
                        photos: reserve.photos,
                        client_key: clientKey,
                    });

                    // Reset form
                    this.form.title = '';
                    this.form.description = '';
                    this.form.severity = 'major';
                    this.form.latitude = null;
                    this.form.longitude = null;
                    this.form.photos = [];

                    this.showToast('Réserve sauvegardée' + (this.isOnline ? '' : ' (sera synchronisée)'));
                    await this.loadReserves();
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
                        const response = await fetch('/api/reserves/sync', {
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
                            this.showToast(`${result.processed} réserve(s) synchronisée(s)`);
                            await this.loadReserves();
                        }
                    } catch (error) {
                        console.error('Sync failed', error);
                    }
                },

                showToast(message) {
                    this.syncToastMessage = message;
                    this.showSyncToast = true;
                    setTimeout(() => { this.showSyncToast = false; }, 4000);
                },

                severityLabel(severity) {
                    return { info: 'Info', minor: 'Mineur', major: 'Majeur', critical: 'Critique' }[severity] || severity;
                },

                statusLabel(status) {
                    return { open: 'Ouverte', in_progress: 'En cours', resolved: 'Résolue', lifted: 'Levée' }[status] || status;
                },
            }));
        });
    </script>
</x-filament-panels::page>
