<x-filament::widget>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($this->getActions() as $action)
            <a href="{{ $action['url'] }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white shadow-sm border border-gray-200 hover:border-{{ $action['color'] }}-400 hover:shadow-md transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-{{ $action['color'] }}-500">
                <div class="p-3 rounded-xl bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/20">
                    <x-dynamic-component :component="$action['icon']" class="w-6 h-6 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" />
                </div>
                <div class="text-center">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $action['label'] }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $action['description'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</x-filament::widget>
