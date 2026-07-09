<x-filament-panels::page>
    <form wire:submit="saveEmployeeData">
        {{ $this->employeeForm }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Enregistrer les modifications
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
