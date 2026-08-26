@php
    $record = $getRecord();
    $url = \Illuminate\Support\Facades\Storage::disk('public')->url($record->file_path);
    $parentUrl = $record->parent_id ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->parent->file_path) : null;
    $format = $record->format;
    $annotations = $record->annotations()->with('target')->get()->toArray();
@endphp

<div
    x-data="bimViewer({
        url: '{{ $url }}',
        parentUrl: '{{ $parentUrl }}',
        format: '{{ strtolower($format) }}',
        annotations: {{ json_encode($annotations) }}
    })"
    @focus-annotation.window="focusAnnotation($event.detail)"
    class="w-full h-full min-h-[600px] bg-gray-900 rounded-xl relative overflow-hidden"
    wire:ignore
>
    <!-- Container 3D -->
    <div x-ref="container" class="w-full h-full absolute inset-0" :class="{'cursor-crosshair': annotationMode || measurementMode}"></div>

    <!-- Treeview / Calques Overlay -->
    <div x-show="showLayers && format === 'ifc'" 
         class="absolute top-4 right-4 z-10 bg-gray-900/90 text-white p-4 rounded-xl shadow-lg border border-gray-700 w-80 max-h-[80%] overflow-y-auto backdrop-blur-sm"
         x-transition>
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Calques (Arbre IFC)</h3>
            <button @click="showLayers = false" class="text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <div x-show="!spatialTree" class="text-sm text-gray-400 italic">
            Chargement de l'arbre...
        </div>
        
        <!-- Recursive Tree Render (simulated via simple list for now, or alpine recursive template if possible) -->
        <div x-show="spatialTree" class="text-sm space-y-1">
            <template x-if="spatialTree">
                <div class="pl-2">
                    <div class="flex items-center gap-2 py-1">
                        <input type="checkbox" checked @change="toggleNode(spatialTree, $event.target.checked)" class="rounded bg-gray-800 border-gray-600 text-primary-600 focus:ring-primary-600">
                        <span class="font-semibold truncate" x-text="spatialTree.type"></span>
                    </div>
                    <!-- On va utiliser une approche simplifiée: lister les enfants de premier ou 2ème niveau -->
                    <template x-for="child in spatialTree.children" :key="child.expressID">
                        <div class="pl-4">
                            <div class="flex items-center gap-2 py-1">
                                <input type="checkbox" checked @change="toggleNode(child, $event.target.checked)" class="rounded bg-gray-800 border-gray-600 text-primary-600">
                                <span class="truncate" x-text="child.type"></span>
                            </div>
                            <template x-for="subchild in child.children" :key="subchild.expressID">
                                <div class="pl-4 flex items-center gap-2 py-1">
                                    <input type="checkbox" checked @change="toggleNode(subchild, $event.target.checked)" class="rounded bg-gray-800 border-gray-600 text-primary-600">
                                    <span class="truncate text-gray-300" x-text="subchild.type"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- UI Overlay (Loading) -->
    <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-75 z-10 transition-opacity">
        <div class="text-white text-center">
            <svg class="animate-spin h-10 w-10 mx-auto mb-4 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="font-medium" x-text="loadingText"></p>
        </div>
    </div>
    
    <!-- Tooltip -->
    <div x-show="tooltip.visible" 
         class="absolute z-20 bg-gray-900/90 text-white p-3 rounded shadow-lg backdrop-blur-sm pointer-events-none border border-gray-700/50"
         :style="`left: ${tooltip.x}px; top: ${tooltip.y}px; transform: translate(-50%, -100%); margin-top: -10px;`"
         x-transition>
        <div class="text-sm font-bold mb-1" x-text="tooltip.title"></div>
        <div x-show="tooltip.targetTitle" class="text-xs text-primary-400 font-semibold" x-text="tooltip.targetTitle"></div>
        <div x-show="tooltip.targetStatus" class="text-xs text-gray-300 mt-1" x-text="`Statut: ${tooltip.targetStatus}`"></div>
        <div class="text-xs text-gray-400 mt-1 italic">Cliquez pour voir les détails</div>
    </div>

    <!-- Controls Overlay -->
    <div class="absolute bottom-4 left-4 z-10 flex gap-2">
        <button type="button" @click="resetCamera" class="bg-gray-800 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-gray-700 transition">
            Réinitialiser vue
        </button>
        <button type="button" @click="toggleAnnotationMode" :class="annotationMode ? 'bg-primary-600 hover:bg-primary-500' : 'bg-gray-800 hover:bg-gray-700'" class="text-white px-3 py-1.5 rounded-lg shadow text-sm transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
            </svg>
            <span x-text="annotationMode ? 'Mode Annotation Actif' : 'Ajouter Punaise'"></span>
        </button>
        <button type="button" x-show="format === 'ifc'" @click="toggleMeasurementMode" :class="measurementMode ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-gray-800 hover:bg-gray-700'" class="text-white px-3 py-1.5 rounded-lg shadow text-sm transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-4.879-4.879l-4.242-4.243m4.242 4.243l-4.243-4.242m4.243 4.242l3.536-3.536m-7.779 3.536L5 9.879m4.879 4.879l4.242-4.243M9.879 14.757l3.536-3.535" />
            </svg>
            <span x-text="measurementMode ? 'Mode Mesure Actif' : 'Mesurer'"></span>
        </button>
        <button type="button" x-show="format === 'ifc' && hasMeasurements" @click="clearMeasurements" class="bg-red-600 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-red-500 transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            Effacer
        </button>
        <button type="button" x-show="format === 'ifc' && hasHiddenElements" @click="showAllElements" class="bg-green-600 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-green-500 transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Tout afficher
        </button>
        <button type="button" x-show="format === 'ifc'" @click="showLayers = !showLayers" class="bg-gray-800 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-gray-700 transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            Calques
        </button>
        <button type="button" x-show="parentUrl && format === 'ifc'" @click="toggleCompare" :class="compareMode ? 'bg-purple-600 hover:bg-purple-500' : 'bg-gray-800 hover:bg-gray-700'" class="text-white px-3 py-1.5 rounded-lg shadow text-sm transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span x-text="compareMode ? 'Quitter Comparaison' : 'Comparer avec V1'"></span>
        </button>
        <div x-show="compareMode" class="flex gap-2 bg-gray-900/80 p-1 rounded-lg backdrop-blur-sm border border-gray-700 items-center px-3">
            <label class="flex items-center gap-2 text-xs text-white cursor-pointer">
                <input type="checkbox" x-model="showDeletedGhosts" @change="updateCompareVisibility" class="rounded bg-gray-800 border-gray-600 text-red-500 focus:ring-red-500">
                Fantômes (Rouge)
            </label>
        </div>
        <button type="button" x-show="arSupported && format === 'ifc' && !arActive" @click="startAR" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-blue-500 transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
            </svg>
            Mode AR
        </button>
        <button type="button" x-show="arActive" @click="exitAR" class="bg-red-600 text-white px-3 py-1.5 rounded-lg shadow text-sm hover:bg-red-500 transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Quitter AR
        </button>
        <button type="button" x-show="format === 'ifc'" @click="openClashModal" class="bg-red-800 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg shadow text-sm transition flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>Clash Detection</span>
        </button>
    </div>
    
    <!-- Reticule pour le Hit-Test AR -->
    <div x-show="arActive && !arModelPlaced" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
        <div class="w-8 h-8 rounded-full border-4 border-white opacity-50"></div>
    </div>

    <!-- Modale Clash Detection -->
    <div x-show="showClashModal" class="absolute inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" x-transition x-cloak>
        <div class="bg-gray-800 p-6 rounded-xl shadow-xl border border-gray-700 w-96 max-w-full relative">
            <h3 class="text-lg font-bold text-white mb-4">Détection de Collisions (AABB)</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Calque A (Type IFC)</label>
                    <select x-model="clashLayerA" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white text-sm focus:ring-primary-500">
                        <option value="">Sélectionner un type...</option>
                        <template x-for="layer in getLayerOptions()" :key="layer.id">
                            <option :value="layer.id" x-text="layer.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Calque B (Type IFC)</label>
                    <select x-model="clashLayerB" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white text-sm focus:ring-primary-500">
                        <option value="">Sélectionner un type...</option>
                        <template x-for="layer in getLayerOptions()" :key="layer.id">
                            <option :value="layer.id" x-text="layer.name"></option>
                        </template>
                    </select>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showClashModal = false" class="px-4 py-2 text-sm text-gray-300 hover:text-white">Annuler</button>
                    <button @click="runClashDetection" :disabled="!clashLayerA || !clashLayerB || clashLayerA === clashLayerB" class="px-4 py-2 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white rounded text-sm font-medium transition">Lancer l'analyse</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar Clash Results -->
    <div x-show="clashesDetected.length > 0 && !showClashModal" class="absolute top-4 left-1/2 -translate-x-1/2 z-40 bg-gray-900/90 text-white px-4 py-3 rounded-xl shadow-lg border border-red-500/50 backdrop-blur-sm flex items-center gap-4" x-transition x-cloak>
        <div class="font-bold text-red-400">
            <span x-text="clashesDetected.length"></span> collisions détectées
        </div>
        <div class="flex gap-2">
            <button @click="clearClashes" class="px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded">Annuler</button>
            <button @click="saveClashes" class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-500 rounded font-medium">Sauvegarder les punaises</button>
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bimViewer', ({ url, parentUrl, format, annotations }) => ({
        url,
        parentUrl,
        format,
        annotations,
        loading: true,
        loadingText: 'Chargement du modèle 3D...',
        annotationMode: false,
        measurementMode: false,
        hasMeasurements: false,
        showLayers: false,
        spatialTree: null,
        hiddenElements: [],
        hasHiddenElements: false,
        modelID: 0,
        tooltip: { visible: false, x: 0, y: 0, title: '', targetTitle: '', targetStatus: '' },
        annotationMeshes: [],
        viewer: null,
        scene: null,
        camera: null,
        renderer: null,
        raycaster: null,
        mouse: null,
        
        // AR State
        arSupported: false,
        arActive: false,
        arModelPlaced: false,
        xrSession: null,
        xrHitTestSource: null,
        xrLocalSpace: null,
        reticle: null,
        modelGroup: null,

        // Compare State
        compareMode: false,
        parentModelID: null,
        showDeletedGhosts: true,
        matDeleted: null,

        // Clash Detection State
        showClashModal: false,
        clashLayerA: '',
        clashLayerB: '',
        clashesDetected: [],
        clashMeshes: [],

        async init() {
            if (!this.url) {
                this.loadingText = 'Aucun modèle disponible.';
                return;
            }

            // Vérifier le support WebXR
            if (navigator.xr) {
                navigator.xr.isSessionSupported('immersive-ar').then((supported) => {
                    this.arSupported = supported;
                });
            }

            try {
                if (this.format === 'ifc') {
                    await this.initIFC();
                } else if (this.format === 'dxf') {
                    await this.initDXF();
                } else {
                    this.loadingText = 'Format non supporté dans la prévisualisation rapide.';
                }
            } catch (error) {
                console.error("Erreur de chargement 3D:", error);
                this.loadingText = 'Erreur lors du chargement.';
            }
        },

        async initIFC() {
            if (!window.IfcViewerAPI) {
                console.error("IfcViewerAPI non trouvé. Vérifiez la compilation JS.");
                this.loadingText = 'Erreur librairie IFC';
                return;
            }

            const container = this.$refs.container;
            const viewer = new window.IfcViewerAPI({ container, backgroundColor: new window.THREE.Color(0x111827) });
            
            // Initialisation de l'environnement Wasm (chemins vers les .wasm copiés dans public/)
            await viewer.IFC.setWasmPath('/');
            
            this.loadingText = 'Chargement IFC en cours...';
            
            try {
                const model = await viewer.IFC.loadIfcUrl(this.url);
                this.modelID = model.modelID;
                viewer.shadowDropper.renderShadow(model.modelID);
                this.viewer = viewer;
                this.loading = false;
                
                // Dessiner les annotations existantes
                this.drawAnnotations();

                // Extraire l'arbre spatial
                try {
                    this.spatialTree = await viewer.IFC.getSpatialStructure(this.modelID);
                    console.log("IFC Spatial Tree:", this.spatialTree);
                } catch(err) {
                    console.error("Erreur lecture arbre IFC", err);
                }

                // Initialiser Raycaster pour le hover
                this.raycaster = new window.THREE.Raycaster();
                this.mouse = new window.THREE.Vector2();

                // Préparer un groupe pour l'AR
                this.modelGroup = new window.THREE.Group();
                const scene = viewer.context.getScene();
                // On pourrait déplacer le modèle dans ce groupe, mais web-ifc-viewer
                // gère les objets à la racine. Pour l'AR, on gèrera la matrice ou le placement 
                // au moment du hit-test.

                // Evénements
                container.addEventListener('click', (event) => this.handleIfcClick(event));
                container.addEventListener('dblclick', (event) => this.handleIfcDblClick(event));
                container.addEventListener('mousemove', (event) => this.handleMouseMove(event));
                window.addEventListener('keydown', (event) => this.handleKeyDown(event));
            } catch(e) {
                console.error(e);
                this.loadingText = "Impossible de charger le fichier IFC.";
            }
        },

        async initDXF() {
            if (!window.DxfViewer) {
                this.loadingText = 'Erreur: la librairie dxf-viewer est manquante.';
                return;
            }
            
            const container = this.$refs.container;
            
            try {
                this.viewer = new window.DxfViewer(container, {
                    autoResize: true,
                });
                
                this.loadingText = 'Chargement DXF en cours...';
                await this.viewer.Load({ url: this.url });
                
                this.loading = false;
                
                // dxf-viewer gère sa propre boucle de rendu, 
                // pas besoin d'appeler requestAnimationFrame manuellement ici.
            } catch(e) {
                console.error(e);
                this.loadingText = "Impossible de charger le fichier DXF.";
            }
        },
        
        animate() {
            // Utilisé uniquement si on gère la scène THREE.js manuellement.
            // Avec web-ifc-viewer et dxf-viewer, ils gèrent leur propre boucle.
            if (this.renderer && this.scene && this.camera) {
                requestAnimationFrame(() => this.animate());
                this.renderer.render(this.scene, this.camera);
            }
        },

        resetCamera() {
            if (this.format === 'ifc' && this.viewer) {
                // Logique spécifique web-ifc (simulé ici par reset basic si possible)
            } else if (this.camera) {
                this.camera.position.set(0, 0, 50);
            }
        },

        getAllIds(node) {
            let ids = [node.expressID];
            if (node.children && node.children.length > 0) {
                node.children.forEach(child => {
                    ids = ids.concat(this.getAllIds(child));
                });
            }
            return ids;
        },

        toggleNode(node, visible) {
            if (!this.viewer || this.format !== 'ifc') return;
            const ids = this.getAllIds(node);
            
            if (visible) {
                // Pour réafficher, on recrée le sous-ensemble principal en incluant tous sauf ce qui est caché
                // Méthode simplifiée : on filtre les ids de hiddenElements
                this.hiddenElements = this.hiddenElements.filter(id => !ids.includes(id));
            } else {
                // Pour cacher
                ids.forEach(id => {
                    if (!this.hiddenElements.includes(id)) this.hiddenElements.push(id);
                });
            }
            
            this.updateVisibility();
        },

        updateVisibility() {
            if (!this.viewer) return;
            if (this.hiddenElements.length > 0) {
                this.hasHiddenElements = true;
                this.viewer.IFC.loader.ifcManager.removeFromSubset(this.modelID, this.hiddenElements);
            } else {
                this.showAllElements();
            }
        },

        showAllElements() {
            if (!this.viewer) return;
            this.hiddenElements = [];
            this.hasHiddenElements = false;
            // Recréer le subset avec tout
            // createSubset sans 'ids' recrée l'objet complet
            this.viewer.IFC.loader.ifcManager.createSubset({
                modelID: this.modelID,
                scene: this.viewer.context.getScene(),
                removePrevious: true,
                customID: 'main-subset' // Or default subset
            });
            // Assurons-nous que removeFromSubset est réinitialisé
            // web-ifc-viewer: removeFromSubset retire simplement de l'affichage. createSubset le restaure.
            // Une approche plus propre si customID n'est pas utilisé :
            // this.viewer.IFC.loader.ifcManager.removeSubset(this.modelID, undefined);
            // et on peut recharger ou utiliser clearSubset
        },

        toggleAnnotationMode() {
            this.annotationMode = !this.annotationMode;
            if (this.annotationMode && this.measurementMode) {
                this.toggleMeasurementMode(); // Désactiver le mode mesure
            }
        },

        toggleMeasurementMode() {
            if (!this.viewer) return;
            this.measurementMode = !this.measurementMode;
            if (this.measurementMode) {
                if (this.annotationMode) this.annotationMode = false;
                this.viewer.dimensions.active = true;
                this.viewer.dimensions.previewActive = true;
                this.hasMeasurements = true; // On affiche le bouton effacer dès l'activation
            } else {
                this.viewer.dimensions.active = false;
                this.viewer.dimensions.previewActive = false;
            }
        },

        clearMeasurements() {
            if (this.viewer && this.viewer.dimensions) {
                this.viewer.dimensions.deleteAll();
                this.hasMeasurements = false;
            }
        },

        handleKeyDown(event) {
            if (this.measurementMode && this.viewer) {
                if (event.key === 'Escape') {
                    this.viewer.dimensions.cancelDrawing();
                } else if (event.key === 'Delete' || event.key === 'Backspace') {
                    this.viewer.dimensions.delete();
                }
            }
        },

        drawAnnotations() {
            if (!this.viewer || !window.THREE) return;
            
            const scene = this.viewer.context.getScene();
            
            this.annotations.forEach(ann => {
                const geometry = new window.THREE.SphereGeometry( 0.5, 32, 16 );
                const material = new window.THREE.MeshBasicMaterial( { color: 0xff0000 } );
                const sphere = new window.THREE.Mesh( geometry, material );
                sphere.position.set(ann.position_x, ann.position_y, ann.position_z);
                
                // Sauvegarder les données pour le raycasting
                sphere.userData = { 
                    id: ann.id, 
                    title: ann.title,
                    targetTitle: ann.target ? ann.target.title : null,
                    targetStatus: ann.target && ann.target.status ? ann.target.status : null
                };
                
                scene.add(sphere);
                this.annotationMeshes.push(sphere);
            });
        },

        handleMouseMove(event) {
            if (this.annotationMode || this.measurementMode || !this.viewer || !window.THREE || this.annotationMeshes.length === 0) {
                this.tooltip.visible = false;
                return;
            }

            const container = this.$refs.container;
            const rect = container.getBoundingClientRect();
            
            this.mouse.x = ((event.clientX - rect.left) / container.clientWidth) * 2 - 1;
            this.mouse.y = -((event.clientY - rect.top) / container.clientHeight) * 2 + 1;

            this.raycaster.setFromCamera(this.mouse, this.viewer.context.getCamera());

            const intersects = this.raycaster.intersectObjects(this.annotationMeshes);

            if (intersects.length > 0) {
                const hovered = intersects[0].object;
                this.tooltip.visible = true;
                this.tooltip.x = event.clientX - rect.left;
                this.tooltip.y = event.clientY - rect.top;
                this.tooltip.title = hovered.userData.title;
                this.tooltip.targetTitle = hovered.userData.targetTitle;
                this.tooltip.targetStatus = hovered.userData.targetStatus;
                container.style.cursor = 'pointer';
            } else {
                this.tooltip.visible = false;
                container.style.cursor = 'default';
            }
        },

        handleIfcClick(event) {
            const container = this.$refs.container;
            
            // Si on est en mode mesure, web-ifc-viewer gère ses propres clics en interne
            if (this.measurementMode) return;

            if (!this.annotationMode) {
                // Check if we clicked on an existing annotation
                if (this.tooltip.visible && this.annotationMeshes.length > 0) {
                    const rect = container.getBoundingClientRect();
                    this.mouse.x = ((event.clientX - rect.left) / container.clientWidth) * 2 - 1;
                    this.mouse.y = -((event.clientY - rect.top) / container.clientHeight) * 2 + 1;
                    this.raycaster.setFromCamera(this.mouse, this.viewer.context.getCamera());
                    const intersects = this.raycaster.intersectObjects(this.annotationMeshes);
                    
                    if (intersects.length > 0) {
                        const clicked = intersects[0].object;
                        // Ouvrir la modale Livewire
                        this.$wire.mountInfolistAction('viewAnnotation', { id: clicked.userData.id });
                    }
                }
                return;
            }

            // Utiliser le raycaster interne de web-ifc-viewer pour placer une punaise
            const result = this.viewer.context.castRayIfc(event);
            
            if (result) {
                const point = result.point;
                // Désactiver le mode après le clic pour éviter d'en poser plusieurs
                this.annotationMode = false;
                
                // Dessiner visuellement la nouvelle punaise temporairement
                const scene = this.viewer.context.getScene();
                const geometry = new window.THREE.SphereGeometry( 0.5, 32, 16 );
                const material = new window.THREE.MeshBasicMaterial( { color: 0x00ff00 } ); // Vert pour la nouvelle
                const sphere = new window.THREE.Mesh( geometry, material );
                sphere.position.copy(point);
                scene.add(sphere);

                // Déclencher l'action Filament Infolist
                this.$wire.mountInfolistAction('createAnnotation', {
                    x: point.x,
                    y: point.y,
                    z: point.z
                });
            }
        },
        
        handleIfcDblClick(event) {
            if (!this.viewer || this.format !== 'ifc') return;
            
            // On cast le ray pour trouver l'élément
            const result = this.viewer.context.castRayIfc(event);
            if (result && result.expressID) {
                const id = result.expressID;
                if (!this.hiddenElements.includes(id)) {
                    this.hiddenElements.push(id);
                    this.updateVisibility();
                }
            }
        },

        focusAnnotation(detail) {
            if (!this.viewer) return;
            const { x, y, z } = detail;
            if (this.format === 'ifc') {
                if (this.viewer.context.ifcCamera.cameraControls) {
                    this.viewer.context.ifcCamera.cameraControls.setLookAt(
                        x + 10, y + 10, z + 10, // Position
                        x, y, z, // Target
                        true // Transition
                    );
                }
            } else if (this.format === 'dxf' && this.camera) {
                this.camera.position.set(x + 10, y + 10, z + 10);
                this.camera.lookAt(x, y, z);
            }
        },

        // --- Comparaison de Révisions (Version Control 3D) ---
        async toggleCompare() {
            if (!this.viewer || this.format !== 'ifc' || !this.parentUrl) return;

            this.compareMode = !this.compareMode;
            
            if (this.compareMode) {
                if (this.parentModelID !== null) {
                    this.updateCompareVisibility();
                    return;
                }
                
                this.loadingText = 'Analyse comparative en cours...';
                this.loading = true;

                try {
                    // Charger le modèle parent (V1)
                    const parentModel = await this.viewer.IFC.loadIfcUrl(this.parentUrl);
                    this.parentModelID = parentModel.modelID;
                    
                    // Masquer le parent par défaut
                    this.viewer.IFC.loader.ifcManager.removeSubset(this.parentModelID, undefined);

                    // Extraire les arbres spatiaux
                    const v1Tree = await this.viewer.IFC.getSpatialStructure(this.parentModelID);
                    const v2Tree = this.spatialTree;

                    const v1Ids = this.getAllIds(v1Tree);
                    const v2Ids = this.getAllIds(v2Tree);

                    const v1GlobalIds = {};
                    const v2GlobalIds = {};

                    // Récupérer les propriétés en bloc (peut être lent, on fait au mieux)
                    for (const id of v1Ids) {
                        try {
                            const props = await this.viewer.IFC.getProperties(this.parentModelID, id, false, false);
                            if (props && props.GlobalId) v1GlobalIds[props.GlobalId.value] = { id, name: props.Name?.value };
                        } catch(e) {}
                    }
                    
                    for (const id of v2Ids) {
                        try {
                            const props = await this.viewer.IFC.getProperties(this.modelID, id, false, false);
                            if (props && props.GlobalId) v2GlobalIds[props.GlobalId.value] = { id, name: props.Name?.value };
                        } catch(e) {}
                    }

                    const addedIds = [];
                    const modifiedIds = [];
                    const deletedIds = [];

                    // Comparer V2 par rapport à V1
                    for (const [globalId, v2Data] of Object.entries(v2GlobalIds)) {
                        if (!v1GlobalIds[globalId]) {
                            addedIds.push(v2Data.id);
                        } else {
                            if (v1GlobalIds[globalId].name !== v2Data.name) {
                                modifiedIds.push(v2Data.id);
                            }
                        }
                    }

                    // Comparer V1 par rapport à V2
                    for (const [globalId, v1Data] of Object.entries(v1GlobalIds)) {
                        if (!v2GlobalIds[globalId]) {
                            deletedIds.push(v1Data.id);
                        }
                    }

                    const scene = this.viewer.context.getScene();

                    // Matériaux
                    const matAdded = new window.THREE.MeshLambertMaterial({ color: 0x10b981, transparent: true, opacity: 0.8 }); // Green
                    const matModified = new window.THREE.MeshLambertMaterial({ color: 0xf59e0b, transparent: true, opacity: 0.8 }); // Orange
                    this.matDeleted = new window.THREE.MeshLambertMaterial({ color: 0xef4444, transparent: true, opacity: 0.3 }); // Red ghost

                    if (addedIds.length > 0) {
                        this.viewer.IFC.loader.ifcManager.createSubset({
                            modelID: this.modelID,
                            ids: addedIds,
                            material: matAdded,
                            scene: scene,
                            removePrevious: true,
                            customID: 'added-subset'
                        });
                    }

                    if (modifiedIds.length > 0) {
                        this.viewer.IFC.loader.ifcManager.createSubset({
                            modelID: this.modelID,
                            ids: modifiedIds,
                            material: matModified,
                            scene: scene,
                            removePrevious: true,
                            customID: 'modified-subset'
                        });
                    }

                    if (deletedIds.length > 0) {
                        this.viewer.IFC.loader.ifcManager.createSubset({
                            modelID: this.parentModelID,
                            ids: deletedIds,
                            material: this.matDeleted,
                            scene: scene,
                            removePrevious: true,
                            customID: 'deleted-subset'
                        });
                    }

                    this.updateCompareVisibility();
                } catch (e) {
                    console.error('Erreur diff:', e);
                } finally {
                    this.loading = false;
                }
            } else {
                // Quitter le mode comparaison
                this.viewer.IFC.loader.ifcManager.removeSubset(this.modelID, undefined, 'added-subset');
                this.viewer.IFC.loader.ifcManager.removeSubset(this.modelID, undefined, 'modified-subset');
                if (this.parentModelID !== null) {
                    this.viewer.IFC.loader.ifcManager.removeSubset(this.parentModelID, undefined, 'deleted-subset');
                }
            }
        },

        updateCompareVisibility() {
            if (!this.compareMode || this.parentModelID === null || !this.matDeleted) return;
            
            this.matDeleted.visible = this.showDeletedGhosts;
        },

        // --- Clash Detection (AABB) ---
        getLayerOptions() {
            if (!this.spatialTree || !this.spatialTree.children) return [];
            return this.extractTypes(this.spatialTree);
        },

        extractTypes(node, types = new Map()) {
            if (node.children) {
                node.children.forEach(c => {
                    if (c.type) {
                        types.set(c.type, { id: c.type, name: c.type });
                    }
                    this.extractTypes(c, types);
                });
            }
            return Array.from(types.values()).sort((a, b) => a.name.localeCompare(b.name));
        },

        getElementsOfType(node, typeName, result = []) {
            if (node.type === typeName) {
                result.push(node.expressID);
            }
            if (node.children) {
                node.children.forEach(c => this.getElementsOfType(c, typeName, result));
            }
            return result;
        },

        openClashModal() {
            this.showClashModal = true;
            this.clashLayerA = '';
            this.clashLayerB = '';
        },

        async runClashDetection() {
            this.showClashModal = false;
            this.loadingText = 'Analyse des collisions en cours...';
            this.loading = true;

            // Laisser le temps à l'UI de s'afficher
            await new Promise(r => setTimeout(r, 100));

            try {
                const idsA = this.getElementsOfType(this.spatialTree, this.clashLayerA);
                const idsB = this.getElementsOfType(this.spatialTree, this.clashLayerB);

                if (idsA.length === 0 || idsB.length === 0) {
                    alert("Aucun élément trouvé pour l'un des calques.");
                    this.loading = false;
                    return;
                }

                const ifcManager = this.viewer.IFC.loader.ifcManager;

                const getBoundingBoxForId = (id) => {
                    const subset = ifcManager.createSubset({
                        modelID: this.modelID,
                        ids: [id],
                        customID: 'temp-clash-' + id
                    });
                    if (subset && subset.geometry) {
                        subset.geometry.computeBoundingBox();
                        const box = subset.geometry.boundingBox.clone();
                        // Appliquer la matrice (souvent identité dans web-ifc mais on assure)
                        box.applyMatrix4(subset.matrixWorld);
                        ifcManager.removeSubset(this.modelID, undefined, 'temp-clash-' + id);
                        return box;
                    }
                    return null;
                };

                const boxesA = [];
                // Limiter à 300 pour éviter les crashs navigateurs
                for (let i = 0; i < Math.min(idsA.length, 300); i++) {
                    const box = getBoundingBoxForId(idsA[i]);
                    if (box) boxesA.push({ id: idsA[i], box });
                }

                const boxesB = [];
                for (let i = 0; i < Math.min(idsB.length, 300); i++) {
                    const box = getBoundingBoxForId(idsB[i]);
                    if (box) boxesB.push({ id: idsB[i], box });
                }

                const clashes = [];
                for (const a of boxesA) {
                    for (const b of boxesB) {
                        if (a.box.intersectsBox(b.box)) {
                            // Point central
                            const intersection = a.box.clone().intersect(b.box);
                            const center = new window.THREE.Vector3();
                            intersection.getCenter(center);
                            
                            clashes.push({
                                layer1: this.clashLayerA,
                                layer2: this.clashLayerB,
                                x: center.x,
                                y: center.y,
                                z: center.z
                            });
                        }
                    }
                }

                this.clashesDetected = clashes;

                // Afficher des sphères violettes pour prévisualiser
                this.clearClashes(false); // Retirer les anciens visuels
                
                const geo = new window.THREE.SphereGeometry(0.8, 16, 16);
                const mat = new window.THREE.MeshBasicMaterial({ color: 0x8b5cf6, transparent: true, opacity: 0.9 });
                
                this.clashesDetected.forEach(clash => {
                    const mesh = new window.THREE.Mesh(geo, mat);
                    mesh.position.set(clash.x, clash.y, clash.z);
                    this.viewer.context.getScene().add(mesh);
                    this.clashMeshes.push(mesh);
                });

            } catch (e) {
                console.error("Erreur Clash:", e);
                alert("Erreur lors de la détection.");
            } finally {
                this.loading = false;
            }
        },

        clearClashes(resetData = true) {
            if (resetData) {
                this.clashesDetected = [];
            }
            if (this.viewer && window.THREE) {
                this.clashMeshes.forEach(m => this.viewer.context.getScene().remove(m));
            }
            this.clashMeshes = [];
        },

        saveClashes() {
            if (this.clashesDetected.length === 0) return;
            this.$wire.mountInfolistAction('saveClashes', { clashes: this.clashesDetected });
            this.clearClashes(true);
            
            // Reload page après 1.5s pour afficher les punaises rouges définitives
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        },

        // --- Logique WebXR (AR) ---
        async startAR() {
            if (!navigator.xr) return;

            try {
                const session = await navigator.xr.requestSession('immersive-ar', { requiredFeatures: ['hit-test'] });
                session.addEventListener('end', () => this.onXREnd());
                
                const renderer = this.viewer.context.getRenderer();
                renderer.xr.enabled = true;
                renderer.xr.setReferenceSpaceType('local');
                await renderer.xr.setSession(session);

                this.xrSession = session;
                this.arActive = true;
                this.arModelPlaced = false;

                // Créer un réticule 3D
                if (!this.reticle) {
                    const geometry = new window.THREE.RingGeometry(0.15, 0.2, 32).rotateX(-Math.PI / 2);
                    const material = new window.THREE.MeshBasicMaterial();
                    this.reticle = new window.THREE.Mesh(geometry, material);
                    this.reticle.matrixAutoUpdate = false;
                    this.reticle.visible = false;
                    this.viewer.context.getScene().add(this.reticle);
                }

                // Gérer le clic (placement du modèle)
                const controller = renderer.xr.getController(0);
                controller.addEventListener('select', () => this.onXRSelect());
                this.viewer.context.getScene().add(controller);

                // Configurer le Hit-Test
                session.requestReferenceSpace('viewer').then((referenceSpace) => {
                    session.requestHitTestSource({ space: referenceSpace }).then((source) => {
                        this.xrHitTestSource = source;
                    });
                });

                session.requestReferenceSpace('local').then((referenceSpace) => {
                    this.xrLocalSpace = referenceSpace;
                });

                // Remplacer la boucle d'animation par celle de WebXR
                renderer.setAnimationLoop((timestamp, frame) => {
                    if (frame) {
                        const referenceSpace = this.xrLocalSpace;
                        if (this.xrHitTestSource && !this.arModelPlaced) {
                            const hitTestResults = frame.getHitTestResults(this.xrHitTestSource);
                            if (hitTestResults.length > 0) {
                                const hit = hitTestResults[0];
                                const pose = hit.getPose(referenceSpace);
                                this.reticle.visible = true;
                                this.reticle.matrix.fromArray(pose.transform.matrix);
                            } else {
                                this.reticle.visible = false;
                            }
                        }
                    }
                    this.viewer.context.render(); // Appeler le rendu interne
                });

            } catch (err) {
                console.error("Erreur lancement AR", err);
                alert("Impossible de démarrer l'AR.");
            }
        },

        onXRSelect() {
            if (this.arModelPlaced || !this.reticle.visible) return;
            
            // Placer le modèle à l'endroit du réticule
            const scene = this.viewer.context.getScene();
            
            // Récupérer le mesh IFC (généralement le premier mesh avec géométrie IFC)
            // On le déplace à la position du réticule
            // L'API web-ifc-viewer ne donne pas de méthode directe pour translater le modèle entier
            // Mais on peut translater la scène ou les meshes enfants.
            scene.children.forEach(child => {
                // Ignore lights, reticle, etc.
                if (child.isMesh && child !== this.reticle && !this.annotationMeshes.includes(child)) {
                    child.position.setFromMatrixPosition(this.reticle.matrix);
                    child.updateMatrixWorld();
                }
            });

            this.arModelPlaced = true;
            this.reticle.visible = false;

            // Transférer aussi les annotations
            this.annotationMeshes.forEach(mesh => {
                mesh.position.add(new window.THREE.Vector3().setFromMatrixPosition(this.reticle.matrix));
            });
        },

        exitAR() {
            if (this.xrSession) {
                this.xrSession.end();
            }
        },

        onXREnd() {
            this.arActive = false;
            this.xrSession = null;
            this.arModelPlaced = false;
            if (this.reticle) this.reticle.visible = false;
            
            const renderer = this.viewer.context.getRenderer();
            renderer.xr.enabled = false;
            
            // Remettre la boucle d'animation normale de web-ifc-viewer
            renderer.setAnimationLoop(null);
            
            // Réinitialiser la position du modèle
            const scene = this.viewer.context.getScene();
            scene.children.forEach(child => {
                if (child.isMesh && child !== this.reticle && !this.annotationMeshes.includes(child)) {
                    child.position.set(0, 0, 0);
                    child.updateMatrixWorld();
                }
            });
            this.resetCamera();
        }
    }));
});
</script>
@endonce
