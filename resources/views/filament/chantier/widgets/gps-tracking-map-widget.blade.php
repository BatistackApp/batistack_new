<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
                <span class="font-bold text-gray-950 dark:text-white">Suivi GPS des Camions</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Positions en temps réel des techniciens sur les interventions en cours.
        </x-slot>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <div
            x-data="{
                map: null,
                tracks: {{ json_encode($this->getActiveTracks()) }},
                initMap() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initMap(), 100);
                        return;
                    }

                    if (this.tracks.length === 0) {
                        this.$refs.gpsMapContainer.innerHTML = `
                            <div class='flex flex-col items-center justify-center h-full bg-gray-50/50 dark:bg-white/5 text-gray-400 dark:text-gray-500'>
                                <p class='text-sm font-semibold'>Aucun technicien géolocalisé en cours.</p>
                            </div>
                        `;
                        return;
                    }

                    const defaultCenter = [46.603354, 1.888334];
                    const initialZoom = this.tracks.length === 1 ? 13 : 7;
                    const mapCenter = [this.tracks[0].lat, this.tracks[0].lng];

                    this.map = L.map(this.$refs.gpsMapContainer).setView(mapCenter, initialZoom);

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a> contributors &copy; <a href=\'https://carto.com/attributions\'>CARTO</a>',
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(this.map);

                    const markerGroup = L.featureGroup().addTo(this.map);

                    this.tracks.forEach((track) => {
                        const markerColor = track.status === 'en_cours' ? '#10b981' : '#f59e0b';

                        const customIcon = L.divIcon({
                            className: 'custom-div-icon',
                            html: `
                                <div class='flex items-center justify-center w-8 h-8 rounded-full shadow-lg border-2 border-white dark:border-gray-900 animate-pulse' style='background-color: ${markerColor}; color: white;'>
                                    <svg class='h-4 w-4' viewBox='0 0 24 24' fill='currentColor'>
                                        <path d='M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z'/>
                                    </svg>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32]
                        });

                        const popupContent = `
                            <div class='p-2 min-w-[220px] text-gray-900 dark:text-gray-100'>
                                <div class='flex justify-between items-center mb-1'>
                                    <span class='font-mono text-[10px] bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded font-bold'>${track.reference}</span>
                                    <span class='text-[9px] uppercase font-bold' style='color: ${markerColor}'>${track.status_label}</span>
                                </div>
                                <h3 class='font-bold text-sm mb-2'>${track.chantier_name}</h3>
                                <p class='text-xs mb-1'><strong>Technicien :</strong> ${track.technicien_name}</p>
                                <p class='text-[10px] text-gray-500 mb-1'><strong>Dernière MAJ :</strong> ${track.recorded_at}</p>
                                <a href='${track.url}' class='block text-center text-xs bg-orange-500 hover:bg-orange-600 text-white font-bold py-1.5 px-3 rounded transition-colors mt-2' style='color: white !important;'>
                                    Voir l'intervention
                                </a>
                            </div>
                        `;

                        L.marker([track.lat, track.lng], { icon: customIcon })
                            .bindPopup(popupContent)
                            .addTo(markerGroup);
                    });

                    if (this.tracks.length > 1) {
                        this.map.fitBounds(markerGroup.getBounds().pad(0.15));
                    }
                }
            }"
            x-init="initMap()"
            x-effect="if (tracks.length > 0 && map) { map.invalidateSize(); }"
            class="mt-4 overflow-hidden rounded-xl border border-gray-100 dark:border-white/10 shadow-sm"
            wire:poll.30s
        >
            <div
                x-ref="gpsMapContainer"
                style="height: 450px; min-height: 450px;"
                class="w-full z-10"
                wire:ignore
            ></div>
        </div>

        <style>
            .leaflet-container { z-index: 1 !important; }
            .leaflet-popup-content-wrapper { border-radius: 12px !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important; border: 1px solid rgba(0,0,0,0.05); }
            .leaflet-popup-tip { box-shadow: none !important; }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
