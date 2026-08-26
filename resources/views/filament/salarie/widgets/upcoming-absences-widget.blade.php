<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-calendar-days"
        :heading="'Prochaines absences (' . $getAbsencesCount() . ')'"
    >
        @if($getAbsences()->isEmpty())
            <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                Aucune absence à venir
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($getAbsences() as $absence)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $absence->type->getLabel() }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $absence->start_date->format('d/m/Y') }} → {{ $absence->end_date->format('d/m/Y') }}
                                ({{ $absence->start_date->diffInDays($absence->end_date) + 1 }} jour{{ ($absence->start_date->diffInDays($absence->end_date) + 1) > 1 ? 's' : '' }})
                            </span>
                        </div>
                        <div>
                            @if($absence->is_paid)
                                <x-filament::badge color="success">Payée</x-filament::badge>
                            @else
                                <x-filament::badge color="warning">Non payée</x-filament::badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($getAbsencesCount() > 5)
                <div class="text-center mt-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        et {{ $getAbsencesCount() - 5 }} autre(s)...
                    </span>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
