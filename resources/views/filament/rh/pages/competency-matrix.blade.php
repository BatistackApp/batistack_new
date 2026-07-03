<x-filament-panels::page>
    <x-filament::card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th class="p-3 border-b dark:border-gray-700 font-semibold sticky left-0 bg-gray-100 dark:bg-gray-800">Employé</th>
                        <!-- Qualifications Columns -->
                        @foreach($this->common_qualifications as $qualName)
                            <th class="p-3 border-b dark:border-gray-700 font-semibold text-center bg-blue-50 dark:bg-blue-900/20" title="Qualification: {{ $qualName }}">
                                <span class="text-blue-600 dark:text-blue-400 font-bold truncate block w-24">Q: {{ Str::limit($qualName, 15) }}</span>
                            </th>
                        @endforeach
                        
                        <!-- Equipements Columns -->
                        @foreach($this->common_equipements as $eqName)
                            <th class="p-3 border-b dark:border-gray-700 font-semibold text-center bg-emerald-50 dark:bg-emerald-900/20" title="Équipement: {{ $eqName }}">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold truncate block w-24">E: {{ Str::limit($eqName, 15) }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->employees as $employee)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="p-3 sticky left-0 bg-white dark:bg-gray-900 font-medium">
                                {{ $employee->full_name }}
                            </td>

                            <!-- Qualifications Check -->
                            @foreach($this->common_qualifications as $qualName)
                                @php
                                    $qual = $employee->qualifications->firstWhere('label.value', $qualName);
                                @endphp
                                <td class="p-3 text-center">
                                    @if($qual)
                                        @if($qual->expires_at && $qual->expires_at < now())
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-danger-100 text-danger-600" title="Expiré le {{ $qual->expires_at->format('d/m/Y') }}">
                                                <x-heroicon-o-x-circle class="w-5 h-5"/>
                                            </span>
                                        @elseif($qual->expires_at && $qual->expires_at < now()->addDays(30))
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-warning-100 text-warning-600" title="Expire bientôt ({{ $qual->expires_at->format('d/m/Y') }})">
                                                <x-heroicon-o-exclamation-triangle class="w-5 h-5"/>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-success-100 text-success-600" title="Valide (Obtenu le {{ $qual->obtained_at ? $qual->obtained_at->format('d/m/Y') : 'N/A' }})">
                                                <x-heroicon-o-check-circle class="w-5 h-5"/>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </td>
                            @endforeach

                            <!-- Equipements Check -->
                            @foreach($this->common_equipements as $eqName)
                                @php
                                    $eq = $employee->equipements->firstWhere('label', $eqName);
                                @endphp
                                <td class="p-3 text-center">
                                    @if($eq)
                                        @if($eq->return_date && $eq->return_date < now())
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-danger-100 text-danger-600" title="À rendre depuis le {{ $eq->return_date->format('d/m/Y') }}">
                                                <x-heroicon-o-x-circle class="w-5 h-5"/>
                                            </span>
                                        @elseif($eq->maintenance_date && $eq->maintenance_date < now())
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-warning-100 text-warning-600" title="Maintenance en retard ({{ $eq->maintenance_date->format('d/m/Y') }})">
                                                <x-heroicon-o-exclamation-triangle class="w-5 h-5"/>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-success-100 text-success-600" title="Conforme">
                                                <x-heroicon-o-check-circle class="w-5 h-5"/>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Legend -->
        <div class="mt-6 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-success-100 text-success-600">
                    <x-heroicon-o-check-circle class="w-4 h-4"/>
                </span>
                Valide / Conforme
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-warning-100 text-warning-600">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4"/>
                </span>
                Expire bientôt / Maintenance requise
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-danger-100 text-danger-600">
                    <x-heroicon-o-x-circle class="w-4 h-4"/>
                </span>
                Expiré / À rendre
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
