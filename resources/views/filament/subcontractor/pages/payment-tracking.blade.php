<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total facturé (HT)</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['total_facture'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>

        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-warning-50 dark:bg-warning-900/20">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Retenues de garantie</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['retenues_garantie'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>

        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success-50 dark:bg-success-900/20">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total payé</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['total_paye'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
