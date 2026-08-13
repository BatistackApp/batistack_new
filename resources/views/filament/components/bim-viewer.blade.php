@php
    $record = $getRecord();
    $url = \Illuminate\Support\Facades\Storage::disk('public')->url($record->file_path);
    $format = $record->format;
    $annotations = $record->annotations()->with('target')->get()->toArray();
@endphp

<div
    x-data="bimViewer({
        url: '{{ $url }}',
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
    </div>
    
    <!-- Reticule pour le Hit-Test AR -->
    <div x-show="arActive && !arModelPlaced" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
        <div class="w-8 h-8 rounded-full border-4 border-white opacity-50"></div>
    </div>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bimViewer', ({ url, format, annotations }) => ({
        url,
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
                // Initialiser le DxfViewer
                this.viewer = new window.DxfViewer(container, {
                    autoResize: true,
                    clearColor: new window.THREE.Color(0x111827),
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
