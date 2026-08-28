<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-document-text"
    >
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold">Mes Bulletins de Paie</h2>
            @if($this->getPayslipsCount() > 3)
                <x-filament::link
                    tag="a"
                    href="{{ route('filament.salarie.resources.paie.payslips.index') }}"
                >
                    Voir tous ({{ $this->getPayslipsCount() }})
                </x-filament::link>
            @endif
        </div>

        @php
            $payslips = $this->getPayslips();
        @endphp

        @if($payslips->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                <p>Aucun bulletin de paie disponible pour le moment.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Période</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Payé</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date de virement</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10 bg-white dark:bg-gray-900">
                        @foreach($payslips as $payslip)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $payslip->period }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    @if($payslip->net_paid)
                                        {{ number_format($payslip->net_paid, 2, ',', ' ') }} €
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($payslip->payment_date)
                                        {{ $payslip->payment_date->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $statusColors = [
                                            'draft' => 'gray',
                                            'validated' => 'warning',
                                            'paid' => 'success',
                                        ];
                                        $color = $statusColors[$payslip->status->value] ?? 'gray';
                                    @endphp
                                    <span class="inline-flex items-center rounded-md bg-{{ $color }}-50 px-2 py-1 text-xs font-medium text-{{ $color }}-700 ring-1 ring-inset ring-{{ $color }}-600/20 dark:bg-{{ $color }}-500/10 dark:text-{{ $color }}-400 dark:ring-{{ $color }}-500/30">
                                        {{ $payslip->status->getLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    @if($payslip->pdf_path)
                                        <x-filament::icon-button
                                            tag="a"
                                            href="{{ \Illuminate\Support\Facades\Storage::url($payslip->pdf_path) }}"
                                            icon="heroicon-o-arrow-down-tray"
                                            label="Télécharger"
                                            target="_blank"
                                            size="sm"
                                        />
                                    @else
                                        <x-filament::icon-button
                                            icon="heroicon-o-arrow-down-tray"
                                            label="PDF non disponible"
                                            size="sm"
                                            disabled
                                        />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
