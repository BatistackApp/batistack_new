<x-filament-panels::page>
    <div 
        class="flex gap-4 overflow-x-auto pb-4"
        x-data="{
            draggingOrderId: null,
            handleDragStart(e, orderId) {
                this.draggingOrderId = orderId;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', orderId);
            },
            handleDrop(e, newStatus) {
                const orderId = e.dataTransfer.getData('text/plain');
                if (orderId && this.draggingOrderId) {
                    $wire.updateOrderStatus(orderId, newStatus);
                }
                this.draggingOrderId = null;
            }
        }"
    >
        @foreach($this->getStatuses() as $status)
            <div 
                class="flex flex-col flex-shrink-0 w-80 bg-gray-100 dark:bg-gray-900 rounded-xl overflow-hidden shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"
                @dragover.prevent="$el.classList.add('bg-gray-200', 'dark:bg-gray-800')"
                @dragleave.prevent="$el.classList.remove('bg-gray-200', 'dark:bg-gray-800')"
                @drop.prevent="
                    $el.classList.remove('bg-gray-200', 'dark:bg-gray-800');
                    handleDrop($event, '{{ $status->value }}');
                "
            >
                <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-white/10">
                    <h3 class="font-medium text-sm text-gray-950 dark:text-white uppercase flex items-center justify-between">
                        {{ $status->getLabel() }}
                        <span class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 py-0.5 px-2 rounded-full text-xs font-semibold">
                            {{ $this->getOrdersByStatus($status->value)->count() }}
                        </span>
                    </h3>
                </div>
                
                <div class="p-3 flex-1 overflow-y-auto space-y-3 min-h-[300px]">
                    @foreach($this->getOrdersByStatus($status->value) as $order)
                        <div 
                            draggable="true"
                            @dragstart="handleDragStart($event, '{{ $order->id }}')"
                            class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-white/10 cursor-grab active:cursor-grabbing hover:border-primary-500 transition-colors"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $order->reference }}</span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-3">
                                {{ $order->item?->name ?? 'Article inconnu' }}
                            </div>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Prévu: <strong class="text-gray-950 dark:text-white">{{ $order->quantity_planned }}</strong></span>
                                @if($order->planned_start_date)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-calendar class="w-3 h-3" />
                                        {{ $order->planned_start_date->format('d/m') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
