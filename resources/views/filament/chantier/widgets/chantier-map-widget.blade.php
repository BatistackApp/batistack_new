<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <!-- Icône Phosphor de boussole / carte -->
                <svg class="h-5 w-5 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-12v12m-9-3.75h.008v.008H6V11.25zm.008 3h.008v.008H6v-.008zm.008 3h.008v.008H6v-.008zm3-6h.008v.008H9V11.25zm.008 3h.008v.008H9v-.008zm.008 3h.008v.008H9v-.008zm3-6h.008v.008h-.008V11.25zm.008 3h.008v.008h-.008v-.008zm.008 3h.008v.008h-.008v-.008zm3-6h.008v.008h-.008V11.25zm.008 3h.008v.008h-.008v-.008z" />
                </svg>
                <span class="font-bold text-gray-950 dark:text-white">Cartographie des Chantiers</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Visualisation géographique et statut opérationnel en temps réel de nos équipes sur le terrain.
        </x-slot>

        <!-- Styles Leaflet nécessaires pour le rendu de la carte -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

        <!-- Script d'initialisation de la carte Leaflet (chargement asynchrone) -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <!-- Wrapper Alpine.js de contrôle de cycle de vie et d'asynchronisme de Leaflet -->
        <div
            x-data="{
                map: null,
                chantiers: {{ json_encode($this->getChantiersLocations()) }},
                initMap() {
                    // Sécurité : si Leaflet n'est pas encore totalement chargé par le navigateur, on temporise
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initMap(), 100);
                        return;
                    }

                    if (this.chantiers.length === 0) {
                        this.$refs.mapContainer.innerHTML = `
                            <div class='flex flex-col items-center justify-center h-full bg-gray-50/50 dark:bg-white/5 text-gray-400 dark:text-gray-500'>
                                <p class='text-sm font-semibold'>Aucun chantier géolocalisé pour le moment.</p>
                            </div>
                        `;
                        return;
                    }

                    const defaultCenter = [46.603354, 1.888334]; // Centre de la France
                    const initialZoom = this.chantiers.length === 1 ? 12 : 6;
                    const mapCenter = this.chantiers.length > 0 ? [this.chantiers[0].lat, this.chantiers[0].lng] : defaultCenter;

                    // Initialisation de la carte sur la référence d'élément Alpine
                    this.map = L.map(this.$refs.mapContainer).setView(mapCenter, initialZoom);

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a> contributors &copy; <a href=\'https://carto.com/attributions\'>CARTO</a>',
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(this.map);

                    const markerGroup = L.featureGroup().addTo(this.map);

                    this.chantiers.forEach((chantier) => {
                        const markerColor = chantier.status_color === 'success' ? '#10b981' : '#f59e0b';

                        const customIcon = L.divIcon({
                            className: 'custom-div-icon',
                            html: `
                                <div class='flex items-center justify-center w-8 h-8 rounded-full shadow-lg border-2 border-white dark:border-gray-900' style='background-color: ${markerColor}; color: white;'>
                                    <svg class='h-4 w-4' viewBox='0 0 24 24' fill='currentColor'>
                                        <path d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z'/>
                                    </svg>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32]
                        });

                        const popupContent = `
                            <div class='p-2 min-w-[200px] text-gray-900 dark:text-gray-100'>
                                <div class='flex justify-between items-center mb-1'>
                                    <span class='font-mono text-[10px] bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded font-bold'>${chantier.reference}</span>
                                    <span class='text-[9px] uppercase font-bold text-orange-600'>${chantier.status_label}</span>
                                </div>
                                <h3 class='font-bold text-sm mb-2 text-[#002157]'>${chantier.name}</h3>
                                <p class='text-xs mb-1'><strong>Conducteur :</strong> ${chantier.manager}</p>
                                <p class='text-[10px] text-gray-500 mb-3'>${chantier.address}</p>
                                <a href='${chantier.url}' class='block text-center text-xs bg-orange-500 hover:bg-orange-600 text-white font-bold py-1.5 px-3 rounded transition-colors' style='color: white !important;'>
                                    Ouvrir le chantier
                                </a>
                            </div>
                        `;

                        L.marker([chantier.lat, chantier.lng], { icon: customIcon })
                            .bindPopup(popupContent)
                            .addTo(markerGroup);
                    });

                    if (this.chantiers.length > 1) {
                        this.map.fitBounds(markerGroup.getBounds().pad(0.15));
                    }
                }
            }"
            x-init="initMap()"
            class="mt-4 overflow-hidden rounded-xl border border-gray-100 dark:border-white/10 shadow-sm"
        >
            <!--
                1. wire:ignore -> Indispensable pour éviter que Livewire détruise le DOM de la carte
                2. style="height: 450px; min-height: 450px;" -> Empêche l'effondrement à 0px de Leaflet
            -->
            <div
                x-ref="mapContainer"
                style="height: 450px; min-height: 450px;"
                class="w-full z-10"
                wire:ignore
            ></div>
        </div>

        <style>
            .leaflet-container {
                z-index: 1 !important;
            }
            .leaflet-popup-content-wrapper {
                border-radius: 12px !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid rgba(0,0,0,0.05);
            }
            .leaflet-popup-tip {
                box-shadow: none !important;
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
