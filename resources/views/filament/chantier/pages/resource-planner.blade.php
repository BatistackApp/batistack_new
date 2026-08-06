<x-filament-panels::page>
    <div class="flex flex-col md:flex-row gap-6">
        
        <!-- Sidebar: Resources -->
        <div class="w-full md:w-1/4 space-y-6">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-users" class="w-5 h-5" />
                        <span>Employés</span>
                    </div>
                </x-slot>
                
                <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                    @foreach($this->employees as $employee)
                        <div 
                            draggable="true" 
                            x-on:dragstart="
                                event.dataTransfer.setData('resourceType', 'employee');
                                event.dataTransfer.setData('resourceId', '{{ $employee->id }}');
                            "
                            class="p-2 border rounded-md shadow-sm bg-white dark:bg-gray-800 cursor-grab active:cursor-grabbing hover:border-primary-500 transition-colors"
                        >
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-truck" class="w-5 h-5" />
                        <span>Véhicules & Équipements</span>
                    </div>
                </x-slot>
                
                <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                    @foreach($this->vehicles as $vehicle)
                        <div 
                            draggable="true" 
                            x-on:dragstart="
                                event.dataTransfer.setData('resourceType', 'vehicle');
                                event.dataTransfer.setData('resourceId', '{{ $vehicle->id }}');
                            "
                            class="p-2 border rounded-md shadow-sm bg-white dark:bg-gray-800 cursor-grab active:cursor-grabbing hover:border-primary-500 transition-colors"
                        >
                            {{ $vehicle->getDisplayName() }}
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Main Grid: Calendar -->
        <div class="w-full md:w-3/4">
            <x-filament::section>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Semaine du {{ \Carbon\Carbon::parse($currentWeekStart)->format('d/m/Y') }}</h2>
                    <div class="flex gap-2">
                        <x-filament::button wire:click="previousWeek" color="gray" size="sm">Précédent</x-filament::button>
                        <x-filament::button wire:click="nextWeek" color="gray" size="sm">Suivant</x-filament::button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="border p-2 bg-gray-50 dark:bg-gray-900 min-w-[200px] text-left">Tâches</th>
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $dayDate = \Carbon\Carbon::parse($currentWeekStart)->addDays($i);
                                    @endphp
                                    <th class="border p-2 bg-gray-50 dark:bg-gray-900 text-center min-w-[120px]">
                                        <div class="text-sm font-normal">{{ $dayDate->locale('fr')->translatedFormat('l') }}</div>
                                        <div class="font-bold">{{ $dayDate->format('d/m') }}</div>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->tasks as $task)
                                <tr>
                                    <td class="border p-2 font-medium">
                                        <div class="text-sm text-gray-500">{{ $task->phase?->chantier?->name ?? 'Sans chantier' }}</div>
                                        <div>{{ $task->label }}</div>
                                    </td>
                                    
                                    @for($i = 0; $i < 7; $i++)
                                        @php
                                            $dayDateStr = \Carbon\Carbon::parse($currentWeekStart)->addDays($i)->format('Y-m-d');
                                            $dayAllocations = $this->allocations->filter(function($alloc) use ($task, $dayDateStr) {
                                                return $alloc->chantier_task_id === $task->id && $alloc->date->format('Y-m-d') === $dayDateStr;
                                            });
                                        @endphp
                                        
                                        <td 
                                            class="border p-2 min-h-[80px] align-top bg-white dark:bg-gray-800 transition-colors duration-200"
                                            x-data="{ isDragOver: false }"
                                            x-on:dragover.prevent="isDragOver = true"
                                            x-on:dragleave.prevent="isDragOver = false"
                                            x-on:drop.prevent="
                                                isDragOver = false;
                                                let type = event.dataTransfer.getData('resourceType');
                                                let id = event.dataTransfer.getData('resourceId');
                                                if(type && id) {
                                                    $wire.allocateResource({{ $task->id }}, type, id, '{{ $dayDateStr }}');
                                                }
                                            "
                                            :class="isDragOver ? 'bg-primary-50 dark:bg-primary-900/30' : ''"
                                        >
                                            <div class="min-h-[60px] flex flex-col gap-1">
                                                @foreach($dayAllocations as $allocation)
                                                    <div class="text-xs p-1 rounded-sm flex justify-between items-center group {{ $allocation->allocatable_type === 'App\Models\RH\Employee' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' }}">
                                                        <span class="truncate">
                                                            @if($allocation->allocatable instanceof \App\Models\RH\Employee)
                                                                {{ $allocation->allocatable->first_name }}
                                                            @elseif($allocation->allocatable instanceof \App\Models\Flottes\Vehicle)
                                                                {{ $allocation->allocatable->getDisplayName() }}
                                                            @else
                                                                Ressource non trouvée
                                                            @endif
                                                        </span>
                                                        <button 
                                                            wire:click="removeAllocation({{ $allocation->id }})"
                                                            class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                                                        >
                                                            <x-filament::icon icon="heroicon-m-x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

    </div>
</x-filament-panels::page>
