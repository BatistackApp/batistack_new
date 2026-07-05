<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">État des Services Auxiliaires</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
            @foreach ($this->getSystemStatus() as $service)
                <div class="flex items-center p-3 border rounded-xl bg-gray-50 dark:bg-gray-900 dark:border-gray-800">
                    <div @class([
                        'p-2 rounded-full mr-4',
                        'bg-success-100 text-success-600' => $service['status'],
                        'bg-danger-100 text-danger-600' => !$service['status'],
                    ])>
                        <x-filament::icon
                            icon="{{ $service['status'] ? \ToneGabes\Filament\Icons\Enums\Phosphor::CheckCircle->getLabel() : \ToneGabes\Filament\Icons\Enums\Phosphor::WarningCircle->getLabel() }}"
                            class="h-6 w-6 text-{{ $service['color'] }}"
                        />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold">{{ $service['label'] }}</h4>
                        <p class="text-xs text-gray-500">{{ $service['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
