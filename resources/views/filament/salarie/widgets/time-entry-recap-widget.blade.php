<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-clock"
        heading="Récapitulatif des pointages"
    >
        {{-- Summary Stats --}}
        @php $summary = $getSummary(); @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $summary['total_hours'] }}h</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $summary['approved_hours'] }}h</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Validées</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $summary['pending_count'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">En attente</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['entry_count'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Pointages</div>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex gap-1 mb-4 p-1 bg-gray-100 dark:bg-white/5 rounded-lg">
            @foreach($getFilterOptions() as $key => $label)
                <button
                    wire:click="setFilter('{{ $key }}')"
                    class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                        {{ $activeFilter === $key
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Table --}}
        @if($getTimeEntries()->isEmpty())
            <div class="text-center py-6 text-sm text-gray-500 dark:text-gray-400">
                Aucun pointage pour cette période
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Chantier</th>
                            <th class="text-right py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Heures</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($getTimeEntries() as $entry)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="py-2 px-3 whitespace-nowrap">{{ $entry->date->format('d/m/Y') }}</td>
                                <td class="py-2 px-3">{{ $entry->chantier?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right font-medium">{{ $entry->hours }}h</td>
                                <td class="py-2 px-3">
                                    <x-filament::badge
                                        :color="match($entry->type) {
                                            \App\Enums\RH\TimeEntryType::OVERTIME_25 => 'warning',
                                            \App\Enums\RH\TimeEntryType::OVERTIME_50 => 'danger',
                                            \App\Enums\RH\TimeEntryType::NIGHT => 'info',
                                            \App\Enums\RH\TimeEntryType::SUNDAY => 'success',
                                            default => 'gray',
                                        }"
                                    >
                                        {{ $entry->type->getLabel() }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2 px-3">
                                    <x-filament::badge
                                        :color="match($entry->status) {
                                            \App\Enums\RH\TimeEntryStatus::APPROVED => 'success',
                                            \App\Enums\RH\TimeEntryStatus::SUBMITTED => 'warning',
                                            \App\Enums\RH\TimeEntryStatus::LOCKED => 'danger',
                                            default => 'gray',
                                        }"
                                    >
                                        {{ $entry->status->getLabel() }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
