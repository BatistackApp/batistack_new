<x-filament-panels::page>
    <div class="mb-4">
        @if($isCompliant)
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-6 h-6 mr-2" />
                    <span class="font-medium">Dossier de vigilance conforme</span>
                </div>
            </div>
        @else
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                    <span class="font-medium">Dossier de vigilance incomplet</span>
                </div>
                <div class="mt-1">
                    <a href="{{ route('filament.sous-traitant.pages.manage-documents') }}" class="underline font-medium">Mettre à jour mes documents</a>
                </div>
            </div>
        @endif
    </div>

    <form wire:submit="saveProfileData">
        {{ $this->profileForm }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Enregistrer le profil
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <form wire:submit="savePasswordData">
            {{ $this->passwordForm }}

            <div class="mt-4">
                <x-filament::button type="submit" color="warning">
                    Changer le mot de passe
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
