<x-filament-panels::page>
    <form wire:submit="simulate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" size="lg">
                Lancer la simulation
            </x-filament::button>
        </div>
    </form>

    @if ($simulationResult)
        <div class="mt-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-filament::section>
                    <x-slot name="heading">
                        Salaire Brut
                    </x-slot>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($simulationResult['gross_salary'], 2, ',', ' ') }} €
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        Net Social
                    </x-slot>
                    <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format($simulationResult['net_social'], 2, ',', ' ') }} €
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        Net Imposable
                    </x-slot>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($simulationResult['taxable_net'], 2, ',', ' ') }} €
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        Coût Total Employeur
                    </x-slot>
                    <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                        {{ number_format($simulationResult['employer_cost'], 2, ',', ' ') }} €
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">
                    Détail des cotisations simulées
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">Catégorie</th>
                                <th class="px-4 py-3">Libellé</th>
                                <th class="px-4 py-3 text-right">Base</th>
                                <th class="px-4 py-3 text-right">Taux Sal.</th>
                                <th class="px-4 py-3 text-right">Montant Sal.</th>
                                <th class="px-4 py-3 text-right">Taux Pat.</th>
                                <th class="px-4 py-3 text-right">Montant Pat.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($simulationResult['lines'] as $line)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium">{{ $line['category'] }}</td>
                                    <td class="px-4 py-3">{{ $line['label'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line['base'], 2, ',', ' ') }} €</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ number_format($line['employee_rate'], 3, ',', ' ') }} %</td>
                                    <td class="px-4 py-3 text-right font-bold text-danger-600">{{ number_format($line['employee_amount'], 2, ',', ' ') }} €</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ number_format($line['employer_rate'], 3, ',', ' ') }} %</td>
                                    <td class="px-4 py-3 text-right font-bold text-warning-600">{{ number_format($line['employer_amount'], 2, ',', ' ') }} €</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800 font-bold">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right">TOTAUX :</td>
                                <td class="px-4 py-3 text-right text-danger-600">{{ number_format($simulationResult['total_employee_contributions'], 2, ',', ' ') }} €</td>
                                <td></td>
                                <td class="px-4 py-3 text-right text-warning-600">{{ number_format($simulationResult['total_employer_contributions'], 2, ',', ' ') }} €</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
