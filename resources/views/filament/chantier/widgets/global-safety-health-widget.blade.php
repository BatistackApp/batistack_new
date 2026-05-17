<x-filament-widgets::widget>
    @php
        $stats = $this->getSafetyStats();
        $rate = $stats['rate'] ?? 100;
        $issues = $stats['issues'] ?? 0;
        $total = $stats['total'] ?? 0;

        // Configuration dynamique des styles selon le taux de conformité
        $colorClass = 'success';
        $textClass = 'text-emerald-600 dark:text-emerald-400';
        $strokeColor = 'text-emerald-600 dark:text-emerald-500';
        $borderClass = 'border-emerald-100 dark:border-emerald-950 bg-emerald-50/20 dark:bg-emerald-950/20';

        if ($rate < 80) {
            $colorClass = 'danger';
            $textClass = 'text-rose-600 dark:text-rose-400';
            $strokeColor = 'text-rose-600 dark:text-rose-500';
            $borderClass = 'border-rose-100 dark:border-rose-950 bg-rose-50/20 dark:bg-rose-950/20';
        } elseif ($rate < 100) {
            $colorClass = 'warning';
            $textClass = 'text-amber-600 dark:text-amber-400';
            $strokeColor = 'text-amber-600 dark:text-amber-500';
            $borderClass = 'border-amber-100 dark:border-amber-950 bg-amber-50/20 dark:bg-amber-950/20';
        }
    @endphp
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <!-- Icône SVG de casque de sécurité -->
                <svg class="h-5 w-5 text-amber-500 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A1.5 1.5 0 0019.5 21l3.75-3.75a1.5 1.5 0 000-2.12l-5.83-5.83m-5.83 5.83l-5.83-5.83m5.83 5.83l1.5 1.5m-7.33-7.33a1.5 1.5 0 11-2.12-2.12l3.75-3.75a1.5 1.5 0 012.12 0l3.75 3.75a1.5 1.5 0 010 2.12l-3.75 3.75a1.5 1.5 0 01-2.12 0z" />
                </svg>
                <span class="font-bold text-gray-950 dark:text-white">Santé & Sécurité Globale</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Suivi réglementaire en temps réel de l'aptitude médicale et des habilitations des équipes actives.
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
            <!-- Cercle de progression (Visualisation du Taux de conformité) -->
            <div class="flex flex-col items-center justify-center p-6 border border-gray-100 dark:border-white/10 rounded-xl bg-gray-50/50 dark:bg-white/5 shadow-sm col-span-1">
                <div class="text-xs uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 mb-4">Indice de conformité</div>
                <div class="relative flex items-center justify-center">
                    <svg class="w-28 h-28 transform -rotate-90">
                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="8" class="text-gray-200 dark:text-gray-800" fill="transparent" />
                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="8" class="{{ $strokeColor }} transition-all duration-500" fill="transparent"
                                stroke-dasharray="301.6"
                                stroke-dashoffset="{{ 301.6 - (301.6 * $rate) / 100 }}" />
                    </svg>
                    <span class="absolute text-2xl font-black text-gray-900 dark:text-white">{{ $rate }}%</span>
                </div>
            </div>

            <!-- Chantiers surveillés et volume d'alertes -->
            <div class="grid grid-cols-1 gap-4 col-span-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Statut Chantiers -->
                    <div class="p-5 border border-gray-100 dark:border-white/10 rounded-xl bg-white dark:bg-white/5 flex items-center space-x-4 shadow-sm">
                        <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400 flex-shrink-0">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18v3H3V3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Chantiers surveillés</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $total }}</div>
                        </div>
                    </div>

                    <!-- Statut Anomalies -->
                    <div class="p-5 border border-gray-100 dark:border-white/10 rounded-xl bg-white dark:bg-white/5 flex items-center space-x-4 shadow-sm">
                        <div class="p-3 rounded-lg flex-shrink-0 {{ $issues > 0 ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400' }}">
                            @if($issues > 0)
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            @else
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.746 3.746 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Anomalies actives</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $issues }}</div>
                        </div>
                    </div>
                </div>

                <!-- Section de notification textuelle en fonction du statut -->
                <div class="p-4 rounded-xl border {{ $borderClass }} flex items-start space-x-3 text-sm">
                    @if($issues > 0)
                        <div class="text-rose-600 dark:text-rose-400 mt-0.5 flex-shrink-0">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 leading-normal text-xs">
                            <span class="font-bold text-gray-900 dark:text-white">Alerte sécurité :</span> Des anomalies réglementaires critiques ont été identifiées. Certains compagnons sur site possèdent une habilitation expirée ou nécessitent une visite médicale.
                        </div>
                    @else
                        <div class="text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 leading-normal text-xs">
                            <span class="font-bold text-gray-900 dark:text-white">Statut conforme :</span> Tout est parfait ! L'ensemble des collaborateurs déployés sur le terrain est à jour de ses visites réglementaires et formations obligatoires.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
