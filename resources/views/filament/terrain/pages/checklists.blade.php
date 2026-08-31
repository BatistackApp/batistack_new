<x-filament-panels::page>
    <div x-data="checklistApp()" x-init="initApp()" class="wizard-container">

        <!-- Status Bar -->
        <div class="flex items-center justify-between p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div x-show="isOnline" class="w-3 h-3 rounded-full animate-pulse bg-green-500"></div>
                <div x-show="!isOnline" class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                <span class="font-medium" x-text="isOnline ? 'Connecté' : 'Hors-ligne'"></span>
                <span class="text-sm text-gray-500 hidden sm:inline">Les checklists sont sauvegardées localement.</span>
            </div>
            <div>
                <button x-show="syncQueue.length > 0 && isOnline" @click="syncData()" type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Synchroniser (<span x-text="syncQueue.length"></span>)
                </button>
            </div>
        </div>

        <!-- Selectors -->
        <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chantier</label>
                    <select x-model="selectedChantierId" @change="loadChecklists()"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <option value="">-- Sélectionner un chantier --</option>
                        <template x-for="chantier in chantiers" :key="chantier.id">
                            <option :value="chantier.id" x-text="chantier.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modèle de checklist</label>
                    <select x-model="selectedTemplateId" @change="loadTemplate()"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <option value="">-- Sélectionner un modèle --</option>
                        <template x-for="tpl in templates" :key="tpl.id">
                            <option :value="tpl.id" x-text="tpl.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!selectedChantierId" class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <x-heroicon-o-clipboard-document-check class="w-12 h-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Checklists de Chantier</h3>
            <p class="text-gray-500 text-sm mt-1">Sélectionnez un chantier et un modèle pour remplir une checklist.</p>
        </div>

        <!-- Wizard Mode -->
        <div x-show="selectedChantierId && selectedTemplateId && currentTemplate && formItems.length > 0" class="space-y-0">

            <!-- Progress Bar (sticky) -->
            <div class="wizard-progress">
                <div class="max-w-lg mx-auto px-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span x-text="currentTemplate?.name"></span>
                        <span x-text="currentItemIndex + 1 + ' / ' + formItems.length"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-orange-500 h-2 rounded-full transition-all duration-300" :style="'width:' + ((currentItemIndex + 1) / formItems.length * 100) + '%'"></div>
                    </div>
                    <!-- Dot indicators -->
                    <div class="flex justify-center gap-1 mt-2">
                        <template x-for="(item, idx) in formItems" :key="'dot-'+idx">
                            <div class="w-2 h-2 rounded-full transition-colors"
                                :class="idx === currentItemIndex ? 'bg-orange-500' : (item.value !== null && item.value !== '' && item.value !== false ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600')">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Current Step Card -->
            <div class="px-4 py-4 max-w-lg mx-auto">
                <template x-for="(item, index) in formItems" :key="'step-'+index">
                    <div x-show="index === currentItemIndex"
                        class="wizard-step-card"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-4">

                        <!-- Item Number & Type Badge -->
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-700 text-sm font-bold dark:bg-orange-900/30 dark:text-orange-400"
                                x-text="index + 1"></span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                                x-text="item.type === 'checkbox' ? 'Checklist' : (item.type === 'file_upload' ? 'Photo' : 'Texte')"></span>
                            <span x-show="item.required" class="text-xs font-medium text-red-500">Obligatoire</span>
                        </div>

                        <!-- Label -->
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="item.label"></h3>

                        <!-- Checkbox Type -->
                        <div x-show="item.type === 'checkbox'" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="item.value ? 'Conforme' : 'Non conforme'"></span>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="item.value" />
                                <div class="toggle-track"></div>
                            </label>
                        </div>

                        <!-- Text Input Type -->
                        <div x-show="item.type === 'text_input'">
                            <textarea x-model="item.value" rows="3"
                                :placeholder="item.required ? 'Observations obligatoires...' : 'Ajouter des observations...'"
                                class="w-full rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 p-4 text-base focus:border-orange-500 focus:ring-orange-500 dark:text-white resize-none"></textarea>
                        </div>

                        <!-- Photo Type -->
                        <div x-show="item.type === 'file_upload'">
                            <template x-if="!item.value">
                                <div>
                                    <label :for="'wizard-photo-' + index"
                                        class="touch-target flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer active:bg-gray-100 dark:active:bg-gray-600 transition-colors">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Capturer une photo</span>
                                        <span class="text-xs text-gray-400 mt-1">Appuyez pour ouvrir l'appareil photo</span>
                                    </label>
                                    <input type="file" :id="'wizard-photo-' + index" accept="image/*" capture="environment"
                                        class="hidden" @change="handlePhoto($event, index)" />
                                </div>
                            </template>
                            <template x-if="item.value">
                                <div class="relative">
                                    <img :src="item.value" class="w-full h-48 object-cover rounded-xl border-2 border-gray-200 dark:border-gray-600" />
                                    <button @click="removePhoto(index)" type="button"
                                        class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg active:scale-90 transition-transform">
                                        ✕
                                    </button>
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs text-center py-2 rounded-b-xl">
                                        Photo capturée
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Auto-save indicator -->
                        <div x-show="autoSaved" x-transition
                            class="mt-3 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Sauvegardé
                        </div>
                    </div>
                </template>

                <!-- Summary view (when on last item and swiping past) -->
                <div x-show="currentItemIndex >= formItems.length"
                    class="wizard-step-card text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Checklist complète !</h3>
                    <p class="text-sm text-gray-500 mb-4"><span x-text="completedCount"></span> / <span x-text="formItems.length"></span> items complétés</p>

                    <!-- Quick summary -->
                    <div class="text-left space-y-2 mb-6">
                        <template x-for="(item, idx) in formItems" :key="'summary-'+idx">
                            <div class="flex items-center gap-2 text-sm">
                                <span :class="item.value ? 'text-green-600' : (item.type === 'checkbox' ? 'text-red-500' : 'text-gray-400')"
                                    x-text="item.type === 'checkbox' ? (item.value ? '✓' : '✗') : (item.value ? '✓' : '—')"></span>
                                <span class="text-gray-700 dark:text-gray-300 truncate" x-text="item.label"></span>
                            </div>
                        </template>
                    </div>

                    <button @click="submitChecklist()" type="button"
                        :disabled="!canSubmit"
                        class="touch-target w-full px-6 py-3 text-base font-medium text-white bg-orange-500 rounded-xl hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition-all shadow-lg">
                        <span x-text="isOnline ? 'Envoyer la checklist' : 'Sauvegarder localement'"></span>
                    </button>
                </div>
            </div>

            <!-- Navigation Bar (fixed bottom) -->
            <div class="wizard-nav" x-show="currentItemIndex < formItems.length">
                <button @click="prevItem()" type="button" :disabled="currentItemIndex === 0"
                    class="touch-target px-5 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed active:scale-[0.97] dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-all">
                    ← Précédent
                </button>
                <div class="flex-1"></div>
                <button @click="nextItem()" type="button"
                    class="touch-target px-5 py-3 text-sm font-medium text-white bg-orange-500 rounded-xl hover:bg-orange-600 active:scale-[0.97] transition-all shadow-md"
                    x-text="currentItemIndex === formItems.length - 1 ? 'Terminer' : 'Suivant →'">
                </button>
            </div>
        </div>

        <!-- History -->
        <div x-show="selectedChantierId && submissions.length > 0" class="space-y-3 mt-4" :style="currentItemIndex < formItems.length && selectedTemplateId ? 'margin-bottom: 80px;' : ''">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Historique des checklists</h3>

            <template x-for="sub in submissions" :key="sub.id || sub.client_key">
                <div class="p-4 rounded-lg bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="sub.template_name || 'Checklist'"></span>
                            <span x-show="sub.synced === false" class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-200">En attente</span>
                            <span x-show="sub.synced === true" class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900/30 dark:text-green-200">Synchronisé</span>
                        </div>
                        <span class="text-xs text-gray-500" x-text="sub.created_at ? new Date(sub.created_at).toLocaleDateString('fr-FR') : ''"></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="sub.completed_items || 0"></span> / <span x-text="sub.total_items || 0"></span> items complétés
                    </div>
                </div>
            </template>
        </div>

        <!-- Loading -->
        <div x-show="isLoading" class="flex justify-center p-8">
            <div class="w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>

    <script src="https://unpkg.com/dexie@4.0.1/dist/dexie.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checklistApp', () => ({
                db: null,
                isOnline: navigator.onLine,
                chantiers: [],
                templates: [],
                submissions: [],
                syncQueue: [],
                selectedChantierId: null,
                selectedTemplateId: null,
                currentTemplate: null,
                formItems: [],
                isLoading: false,
                currentItemIndex: 0,
                autoSaved: false,
                autoSaveTimer: null,
                touchStartX: 0,
                touchStartY: 0,

                get progressPercent() {
                    if (this.formItems.length === 0) return 0;
                    const filled = this.formItems.filter(item => {
                        if (item.type === 'checkbox') return item.value === true;
                        if (item.type === 'file_upload') return item.value !== null;
                        return item.value && String(item.value).trim() !== '';
                    }).length;
                    return Math.round((filled / this.formItems.length) * 100);
                },

                get completedCount() {
                    return this.formItems.filter(item => {
                        if (item.type === 'checkbox') return item.value === true;
                        if (item.type === 'file_upload') return item.value !== null;
                        return item.value && String(item.value).trim() !== '';
                    }).length;
                },

                get canSubmit() {
                    const requiredItems = this.formItems.filter(i => i.required);
                    return requiredItems.length === 0 || requiredItems.every(item => {
                        if (item.type === 'checkbox') return item.value === true;
                        if (item.type === 'file_upload') return item.value !== null;
                        return item.value && String(item.value).trim() !== '';
                    });
                },

                async initApp() {
                    this.db = new Dexie('batistack_checklist_db');
                    this.db.version(1).stores({
                        submissions: 'id, chantier_id, template_id, client_key, synced',
                        sync_queue: '++id, type, payload'
                    });

                    window.addEventListener('online', () => { this.isOnline = true; this.syncData(); });
                    window.addEventListener('offline', () => { this.isOnline = false; });

                    // Swipe gesture handling
                    document.addEventListener('touchstart', (e) => {
                        this.touchStartX = e.touches[0].clientX;
                        this.touchStartY = e.touches[0].clientY;
                    }, { passive: true });

                    document.addEventListener('touchend', (e) => {
                        const dx = e.changedTouches[0].clientX - this.touchStartX;
                        const dy = e.changedTouches[0].clientY - this.touchStartY;
                        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 60) {
                            if (dx < 0) this.nextItem();
                            else this.prevItem();
                        }
                    }, { passive: true });

                    await this.loadChantiers();
                    await this.loadTemplates();
                },

                async loadChantiers() {
                    try {
                        const response = await fetch('/api/checklist/chantiers', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        this.chantiers = data.data || [];
                    } catch (error) {
                        console.error('Failed to fetch chantiers', error);
                    }
                },

                async loadTemplates() {
                    try {
                        const response = await fetch('/api/checklist/templates', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        this.templates = data.data || [];
                    } catch (error) {
                        console.error('Failed to fetch templates', error);
                    }
                },

                loadTemplate() {
                    this.currentItemIndex = 0;
                    if (!this.selectedTemplateId) {
                        this.currentTemplate = null;
                        this.formItems = [];
                        return;
                    }
                    this.currentTemplate = this.templates.find(t => t.id == this.selectedTemplateId);
                    if (this.currentTemplate && this.currentTemplate.schema) {
                        this.formItems = this.currentTemplate.schema.map(block => ({
                            type: block.type,
                            name: block.data.name,
                            label: block.data.label,
                            required: block.data.required || false,
                            value: block.type === 'checkbox' ? false : null,
                        }));
                    }
                    this.loadSubmissions();
                },

                nextItem() {
                    if (this.currentItemIndex < this.formItems.length) {
                        this.currentItemIndex++;
                        this.triggerAutoSave();
                    }
                },

                prevItem() {
                    if (this.currentItemIndex > 0) {
                        this.currentItemIndex--;
                    }
                },

                triggerAutoSave() {
                    this.autoSaved = false;
                    clearTimeout(this.autoSaveTimer);
                    this.autoSaveTimer = setTimeout(() => {
                        this.autoSaved = true;
                        setTimeout(() => { this.autoSaved = false; }, 2000);
                    }, 500);
                },

                async loadSubmissions() {
                    if (!this.selectedChantierId) return;
                    this.isLoading = true;
                    try {
                        const localSubs = await this.db.submissions
                            .where('chantier_id').equals(parseInt(this.selectedChantierId))
                            .toArray();
                        this.submissions = localSubs.map(s => ({
                            ...s,
                            synced: s.id && !String(s.id).startsWith('local_')
                        }));

                        if (this.isOnline) {
                            const response = await fetch(`/api/checklist/submissions?chantier_id=${this.selectedChantierId}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await response.json();
                            const serverSubs = data.data || [];

                            await this.db.submissions
                                .where('chantier_id').equals(parseInt(this.selectedChantierId))
                                .delete();

                            for (const sub of serverSubs) {
                                await this.db.submissions.put({
                                    id: sub.id,
                                    chantier_id: sub.chantier_id,
                                    template_id: sub.checklist_template_id,
                                    template_name: sub.template_name,
                                    completed_items: sub.completed_items,
                                    total_items: sub.total_items,
                                    created_at: sub.created_at,
                                    synced: true,
                                });
                            }
                            this.submissions = serverSubs.map(s => ({ ...s, synced: true }));
                        }
                    } catch (error) {
                        console.error('Failed to load submissions', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                handlePhoto(event, index) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        // Compress before storing
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
                            this.formItems[index].value = canvas.toDataURL('image/jpeg', 0.8);
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                removePhoto(index) {
                    this.formItems[index].value = null;
                },

                async submitChecklist() {
                    if (!this.canSubmit) return;

                    const clientKey = crypto.randomUUID ? crypto.randomUUID() : 'ck-' + Date.now();
                    const data = {};
                    let completedItems = 0;

                    this.formItems.forEach(item => {
                        const val = item.type === 'checkbox' ? !!item.value : (item.value || null);
                        data[item.name] = val;
                        if (item.type === 'checkbox' && item.value) completedItems++;
                        else if (item.type !== 'checkbox' && item.value) completedItems++;
                    });

                    const submission = {
                        id: 'local_' + clientKey,
                        chantier_id: parseInt(this.selectedChantierId),
                        template_id: parseInt(this.selectedTemplateId),
                        template_name: this.currentTemplate?.name || 'Checklist',
                        data: data,
                        completed_items: completedItems,
                        total_items: this.formItems.length,
                        created_at: new Date().toISOString(),
                        synced: false,
                    };

                    await this.db.submissions.put(submission);

                    await this.queueOperation('CREATE_SUBMISSION', {
                        chantier_id: submission.chantier_id,
                        checklist_template_id: submission.template_id,
                        data: data,
                        completed_items: completedItems,
                        total_items: this.formItems.length,
                        client_key: clientKey,
                    });

                    // Reset
                    this.formItems.forEach(item => {
                        item.value = item.type === 'checkbox' ? false : null;
                    });
                    this.currentItemIndex = 0;
                    this.selectedTemplateId = null;
                    this.currentTemplate = null;
                    this.formItems = [];

                    await this.loadSubmissions();
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
                        const response = await fetch('/api/checklist/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({ operations: this.syncQueue })
                        });

                        // Handle CSRF token expiration (419)
                        if (response.status === 419) {
                            this.showToast('Session expirée. Veuillez rafraîchir la page.', 'error');
                            return;
                        }

                        const result = await response.json();
                        if (result.success) {
                            await this.db.sync_queue.clear();
                            this.syncQueue = [];
                            await this.loadSubmissions();
                        }
                    } catch (error) {
                        console.error('Sync failed', error);
                    }
                },

                showToast(message, type = 'info') {
                    const toast = document.createElement('div');
                    const bgColor = type === 'error' ? 'bg-red-600' : 'bg-emerald-600';
                    toast.className = `fixed bottom-4 right-4 z-[9999] px-4 py-3 ${bgColor} text-white rounded-lg shadow-lg flex items-center gap-2 transition-opacity`;
                    toast.innerHTML = '<span>' + message + '</span>';
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 5000);
                },
            }));
        });
    </script>
</x-filament-panels::page>
