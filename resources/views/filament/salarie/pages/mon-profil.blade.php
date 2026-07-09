<x-filament-panels::page>
    <x-filament-panels::form wire:submit="saveEmployeeData">
        {{ $this->employeeForm }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('saveEmployeeData')
                    ->label('Enregistrer les modifications')
                    ->submit('saveEmployeeData'),
            ]"
        />
    </x-filament-panels::form>

    <x-filament::section>
        <x-filament-panels::form wire:submit="savePasswordData">
            {{ $this->passwordForm }}

            <x-filament-panels::form.actions
                :actions="[
                    \Filament\Actions\Action::make('savePasswordData')
                        ->label('Changer le mot de passe')
                        ->submit('savePasswordData')
                        ->color('warning'),
                ]"
                class="mt-4"
            />
        </x-filament-panels::form>
    </x-filament::section>
</x-filament-panels::page>
