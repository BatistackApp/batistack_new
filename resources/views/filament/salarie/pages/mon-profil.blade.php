<x-filament-panels::page>
    <x-filament-panels::form wire:submit="saveEmployeeData">
        {{ $this->employeeForm }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Enregistrer les modifications
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <x-filament::section>
        <x-filament-panels::form wire:submit="savePasswordData">
            {{ $this->passwordForm }}

            <div class="mt-4">
                <x-filament::button type="submit" color="warning">
                    Changer le mot de passe
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </x-filament::section>
</x-filament-panels::page>
