<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-primary-500/10 p-3 rounded-xl text-primary-500">
                    <x-filament::icon
                        icon="heroicon-m-rocket-launch"
                        class="h-6 w-6"
                    />
                </div>
                <div>
                    <h2 class="text-sm font-medium tracking-tight text-gray-500 dark:text-gray-400">
                        Version de l'Application
                    </h2>
                    <p class="text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ $version }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Dernière version déployée en production
                    </p>
                </div>
            </div>

            <div class="flex justify-start md:justify-end">
                {{ $this->viewReleaseNotesAction }}
            </div>
        </div>
    </x-filament::section>
    
    <x-filament-actions::modals />
</x-filament-widgets::widget>
