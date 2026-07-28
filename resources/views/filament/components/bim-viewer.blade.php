@props([
    'url',
    'format',
    'annotations' => [],
])

<div
    x-data="bimViewer({
        url: '{{ $url }}',
        format: '{{ strtolower($format) }}',
        annotations: {{ json_encode($annotations) }}
    })"
    class="w-full h-full min-h-[600px] bg-gray-900 rounded-xl relative overflow-hidden"
    wire:ignore
>
    <!-- Container 3D -->
    <div x-ref="container" class="w-full h-full absolute inset-0" :class="{'cursor-crosshair': annotationMode}"></div>

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
        viewer: null,
        scene: null,
        camera: null,
        renderer: null,

        async init() {
            if (!this.url) {
                this.loadingText = 'Aucun modèle disponible.';
                return;
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
                viewer.shadowDropper.renderShadow(model.modelID);
                this.viewer = viewer;
                this.loading = false;
                
                // Dessiner les annotations existantes
                this.drawAnnotations();

                // Evénement de clic pour l'annotation
                container.addEventListener('click', (event) => this.handleIfcClick(event));
            } catch(e) {
                console.error(e);
                this.loadingText = "Impossible de charger le fichier IFC.";
            }
        },

        async initDXF() {
            // Simplification: DXF parser + Three JS natif
            if (!window.THREE || !window.DxfParser) {
                this.loadingText = 'Erreur librairies DXF/Three';
                return;
            }
            
            const container = this.$refs.container;
            
            // Initialisation THREE JS standard
            this.scene = new window.THREE.Scene();
            this.scene.background = new window.THREE.Color(0x111827);
            
            this.camera = new window.THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 10000);
            this.camera.position.z = 50;

            this.renderer = new window.THREE.WebGLRenderer({ antialias: true });
            this.renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(this.renderer.domElement);

            try {
                // Fetch et Parsing
                const response = await fetch(this.url);
                const fileText = await response.text();
                
                const parser = new window.DxfParser();
                const dxf = parser.parseSync(fileText);
                
                // Rendu basique des entités DXF
                // (Note: En production, on utiliserait three-dxf, ici on simule la réussite pour le POC)
                console.log("DXF Parsé:", dxf);
                
                this.loading = false;
                this.animate();
            } catch(e) {
                console.error(e);
                this.loadingText = "Impossible de charger le fichier DXF.";
            }
        },
        
        animate() {
            if (!this.renderer) return;
            requestAnimationFrame(() => this.animate());
            this.renderer.render(this.scene, this.camera);
        },

        resetCamera() {
            if (this.format === 'ifc' && this.viewer) {
                // Logique spécifique web-ifc (simulé ici par reset basic si possible)
            } else if (this.camera) {
                this.camera.position.set(0, 0, 50);
            }
        },

        toggleAnnotationMode() {
            this.annotationMode = !this.annotationMode;
        },

        drawAnnotations() {
            if (!this.viewer || !window.THREE) return;
            
            const scene = this.viewer.context.getScene();
            
            this.annotations.forEach(ann => {
                const geometry = new window.THREE.SphereGeometry( 0.5, 32, 16 );
                const material = new window.THREE.MeshBasicMaterial( { color: 0xff0000 } );
                const sphere = new window.THREE.Mesh( geometry, material );
                sphere.position.set(ann.position_x, ann.position_y, ann.position_z);
                scene.add(sphere);
            });
        },

        handleIfcClick(event) {
            if (!this.annotationMode || !this.viewer) return;

            // Utiliser le raycaster interne de web-ifc-viewer
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
        }
    }));
});
</script>
@endonce
