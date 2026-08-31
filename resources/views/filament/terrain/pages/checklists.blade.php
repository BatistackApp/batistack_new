<x-filament-panels::page>
    <div x-data="checklistApp()" x-init="initApp()" class="space-y-6">

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

        <!-- Checklist Form -->
        <div x-show="selectedChantierId && selectedTemplateId && currentTemplate" class="space-y-4">
            <div class="p-4 rounded-lg bg-white shadow dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1" x-text="currentTemplate?.name"></h3>
                <p class="text-sm text-gray-500 mb-4" x-text="currentTemplate?.description"></p>

                <!-- Progress -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>Progression</span>
                        <span x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width:' + progressPercent + '%'"></div>
                    </div>
                </div>

                <!-- Items -->
                <div class="space-y-3">
                    <template x-for="(item, index) in formItems" :key="item.name">
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                            <!-- Checkbox type -->
                            <div x-show="item.type === 'checkbox'" class="flex items-center gap-3">
                                <input type="checkbox" :id="'item-' + index"
                                    x-model="item.value"
                                    class="w-5 h-5 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <label :for="'item-' + index" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer" x-text="item.label"></label>
                            </div>

                            <!-- Text input type -->
                            <div x-show="item.type === 'text_input'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="item.label"></label>
                                <input type="text" x-model="item.value"
                                    :placeholder="item.required ? 'Obligatoire' : 'Optionnel'"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" />
                            </div>

                            <!-- Photo type -->
                            <div x-show="item.type === 'file_upload'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="item.label"></label>
                                <div class="mt-1 flex items-center gap-3">
                                    <label :for="'photo-' + index"
                                        class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400">
                                        📷 Prendre une photo
                                    </label>
                                    <input type="file" :id="'photo-' + index" accept="image/*" capture="environment"
                                        class="hidden" @change="handlePhoto($event, index)" />
                                    <span x-show="item.value" class="text-sm text-green-600">✓ Photo capturée</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Submit -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button @click="submitChecklist()" type="button"
                        :disabled="!canSubmit"
                        class="flex-1 px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-text="isOnline ? 'Envoyer la checklist' : 'Sauvegarder localement'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- History -->
        <div x-show="selectedChantierId && submissions.length > 0" class="space-y-3">
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
            <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
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

                get progressPercent() {
                    if (this.formItems.length === 0) return 0;
                    const filled = this.formItems.filter(item => {
                        if (item.type === 'checkbox') return item.value === true;
                        if (item.type === 'file_upload') return item.value !== null;
                        return item.value && String(item.value).trim() !== '';
                    }).length;
                    return Math.round((filled / this.formItems.length) * 100);
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
                        this.formItems[index].value = e.target.result;
                    };
                    reader.readAsDataURL(file);
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

                    this.formItems.forEach(item => {
                        item.value = item.type === 'checkbox' ? false : null;
                    });

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
            }));
        });
    </script>
</x-filament-panels::page>
