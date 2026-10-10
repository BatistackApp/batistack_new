<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total facturé TTC</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['total_facture'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>

        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-warning-50 dark:bg-warning-900/20">
                    <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Reste à encaisser</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['reste_a_encaisser'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>

        <div class="filament-card bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success-50 dark:bg-success-900/20">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total encaissé</div>
                    <div class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($tableData['total_paye'], 2, ',', ' ') }} €</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8">
        {{ $this->table }}
    </div>

    <section class="space-y-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Factures clients</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Factures émises par l'entreprise à votre société.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Référence</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Statut</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Échéance</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Montant TTC</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Réglé</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Reste à encaisser</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($this->getCustomerInvoices() as $invoice)
                        @php
                            $allocated = (float) ($invoice->allocations_sum_allocated_amount ?? 0);
                            $remaining = max(0, (float) $invoice->total_ttc - $allocated);
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-950 dark:text-white">{{ $invoice->reference }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $invoice->status?->getLabel() ?? $invoice->status?->value ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} €</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format($allocated, 2, ',', ' ') }} €</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ number_format($remaining, 2, ',', ' ') }} €</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($remaining > 0 && in_array($invoice->status?->value, ['validated', 'partially_paid', 'payment_in_progress'], true))
                                    <a
                                        href="{{ URL::temporarySignedRoute('pay.subcontractor.invoice', now()->addMinutes(30), ['invoice' => $invoice->getKey()]) }}"
                                        class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                    >
                                        Payer
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune facture client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
