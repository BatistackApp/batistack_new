<x-filament-panels::page>
    <div x-data="etatDesLieuxApp()" x-init="initApp" class="space-y-6">

        <!-- Status Bar -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
                <span class="text-sm text-gray-500">Les photos sont conservées hors-ligne et synchronisées ensuite.</span>
            </div>
            <div>
                <button x-show="syncQueue.length > 0 && isOnline" @click="syncData" type="button" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    Synchroniser (<span x-text="syncQueue.length"></span>)
                </button>
            </div>
        </div>

        <!-- Info : preuve juridique -->
        <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200">
            <strong>Protection contre les litiges fournisseurs.</strong> L'horodatage est enregistré côté serveur à la synchronisation, garantissant l'intégrité des preuves (réception / restitution du matériel loué).
        </div>

        <!-- Empty State -->
        <div x-show="contracts.length === 0" style="display: none;" class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <x-heroicon-o-truck class="w-12 h-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun contrat de location</h3>
            <p class="text-gray-500 text-sm mt-1">Aucun matériel loué n'est affecté à vos chantiers, ou la liste est en cours de chargement.</p>
        </div>

        <!-- Contract List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="contract in contracts" :key="contract.id">
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="contract.reference"></h3>
                            <p class="text-sm text-gray-500" x-text="contract.name"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="contract.chantier?.name || 'Sans chantier'"></p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" x-text="contract.status"></span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button @click="openReportModal(contract, 'reception')" type="button" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                            État des Lieux - Réception
                        </button>
                        <button @click="openReportModal(contract, 'restitution')" type="button" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            État des Lieux - Restitution
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Report Modal -->
        <div x-show="isReportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
            <div class="w-full max-w-lg p-6 bg-white rounded-xl dark:bg-gray-800 shadow-xl m-4" @click.away="isReportModalOpen = false">
                <h3 class="text-lg font-bold mb-1" x-text="'État des Lieux - ' + (currentType === 'reception' ? 'Réception' : 'Restitution')"></h3>
                <p class="text-sm text-gray-500 mb-4" x-text="currentContract ? currentContract.reference + ' - ' + (currentContract.chantier?.name || '') : ''"></p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Commentaire</label>
                        <textarea x-model="report.comment" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600" placeholder="État constaté (ex: rayure, choc...)"></textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Position GPS</span>
                        <span class="text-xs text-gray-500" x-text="report.latitude ? report.latitude.toFixed(6) + ', ' + report.longitude.toFixed(6) : 'Non capturée'"></span>
                        <button @click="captureGPS" type="button" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">GPS</button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photos (<span x-text="report.photos.length"></span>)</label>
                        <button @click="capturePhoto" type="button" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Prendre une Photo</button>
                        <div class="grid grid-cols-4 gap-2 mt-2">
                            <template x-for="(photo, index) in report.photos" :key="index">
                                <img :src="photo" class="w-full h-20 object-cover rounded-lg" />
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Signature</label>
                        <div class="relative border border-gray-300 rounded-lg overflow-hidden dark:border-gray-600">
                            <canvas x-ref="signatureCanvas" class="w-full h-32 bg-gray-50 dark:bg-gray-700"></canvas>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button @click="clearSignature" type="button" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Effacer</button>
                            <span class="text-xs text-gray-500 self-center" x-text="report.signature ? 'Signé' : 'Non signé'"></span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="isReportModalOpen = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
                    <button @click="saveReport" type="button" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Enregistrer</button>
                </div>
            </div>
        </div>

    </div>

    <script src="https://unpkg.com/dexie@4.0.1/dist/dexie.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('etatDesLieuxApp', () => ({
                db: null,
                isOnline: navigator.onLine,
                contracts: [],
                syncQueue: [],
                isReportModalOpen: false,
                currentContract: null,
                currentType: 'reception',
                report: {
                    comment: '',
                    latitude: null,
                    longitude: null,
                    photos: [],
                    signature: null,
                    clientKey: null,
                },

                async initApp() {
                    this.db = new Dexie("batistack_etat_des_lieux_db");
                    this.db.version(1).stores({
                        contracts: 'id, reference, status, chantier_id',
                        sync_queue: '++id, type, payload'
                    });

                    window.addEventListener('online', () => { this.isOnline = true; this.syncData(); });
                    window.addEventListener('offline', () => { this.isOnline = false; });

                    await this.loadLocalData();

                    if (this.isOnline) {
                        await this.fetchFromServer();
                    }
                },

                async loadLocalData() {
                    this.contracts = await this.db.contracts.toArray();
                    this.syncQueue = await this.db.sync_queue.toArray();
                },

                async fetchFromServer() {
                    try {
                        const response = await fetch('/api/etat-des-lieux/contracts', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        if (data.data) {
                            await this.db.contracts.clear();
                            await this.db.contracts.bulkAdd(data.data);
                            this.contracts = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to fetch contracts', error);
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
                        const response = await fetch('/api/etat-des-lieux/sync', {
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
                        }
                    } catch (error) {
                        console.error('Sync failed', error);
                    }
                },

                openReportModal(contract, type) {
                    this.currentContract = contract;
                    this.currentType = type;
                    this.report = {
                        comment: '',
                        latitude: null,
                        longitude: null,
                        photos: [],
                        signature: null,
                        clientKey: crypto.randomUUID ? crypto.randomUUID() : 'ck-' + Date.now(),
                    };
                    this.isReportModalOpen = true;
                    this.$nextTick(() => this.initSignatureCanvas());
                },

                initSignatureCanvas() {
                    const canvas = this.$refs.signatureCanvas;
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');
                    let drawing = false;
                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                    ctx.fillStyle = '#f9fafb';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.strokeStyle = '#111827';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';

                    const pos = (e) => {
                        const rect = canvas.getBoundingClientRect();
                        const point = e.touches ? e.touches[0] : e;
                        return { x: point.clientX - rect.left, y: point.clientY - rect.top };
                    };

                    canvas.addEventListener('mousedown', (e) => { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
                    canvas.addEventListener('mousemove', (e) => { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
                    canvas.addEventListener('mouseup', () => { drawing = false; });
                    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
                    canvas.addEventListener('touchmove', (e) => { e.preventDefault(); if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
                    canvas.addEventListener('touchend', () => { drawing = false; });
                },

                clearSignature() {
                    const canvas = this.$refs.signatureCanvas;
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        ctx.fillStyle = '#f9fafb';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                    }
                    this.report.signature = null;
                },

                captureGPS() {
                    if (!navigator.geolocation) { alert("La géolocalisation n'est pas supportée."); return; }
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.report.latitude = position.coords.latitude;
                        this.report.longitude = position.coords.longitude;
                    }, () => { alert("Impossible de récupérer la position."); });
                },

                capturePhoto() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.capture = 'environment';
                    input.onchange = (e) => {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onloadend = async () => {
                                this.report.photos.push(reader.result);
                            };
                            reader.readAsDataURL(file);
                        }
                    };
                    input.click();
                },

                getSignatureData() {
                    const canvas = this.$refs.signatureCanvas;
                    if (!canvas) return null;
                    const ctx = canvas.getContext('2d');
                    const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                    let hasInk = false;
                    for (let i = 3; i < pixels.length; i += 4) { if (pixels[i] > 0) { hasInk = true; break; } }
                    if (!hasInk) return null;
                    return canvas.toDataURL('image/png');
                },

                async saveReport() {
                    if (!this.currentContract) return;
                    this.report.signature = this.getSignatureData();

                    await this.queueOperation('CREATE_REPORT', {
                        contract_id: this.currentContract.id,
                        type: this.currentType,
                        client_key: this.report.clientKey,
                        comment: this.report.comment || null,
                        latitude: this.report.latitude,
                        longitude: this.report.longitude,
                        signature: this.report.signature,
                    });

                    for (const photo of this.report.photos) {
                        await this.queueOperation('UPLOAD_PHOTO', {
                            report_key: this.report.clientKey,
                            image: photo
                        });
                    }

                    this.isReportModalOpen = false;
                    alert('État des lieux enregistré' + (this.isOnline ? ' et synchronisé.' : ', en attente de synchronisation.'));
                }
            }));
        });
    </script>
</x-filament-panels::page>