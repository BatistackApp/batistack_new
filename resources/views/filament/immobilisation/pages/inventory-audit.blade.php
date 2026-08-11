<x-filament-panels::page>
    <x-filament::card>
        <div class="space-y-4">
            <p class="text-gray-600 dark:text-gray-400">
                Utilisez votre douchette code-barre ou scannez le QR code de l'actif. Assurez-vous que le curseur est dans le champ ci-dessous.
            </p>
            
            <form wire:submit="processScan">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="scannedUrl"
                        placeholder="Scanner le QR Code ici..."
                        autofocus
                    />
                </x-filament::input.wrapper>
                <div class="mt-4">
                    <x-filament::button type="submit" color="primary">
                        Valider manuellement
                    </x-filament::button>
                </div>
            </form>
        </div>
    </x-filament::card>
</x-filament-panels::page>
