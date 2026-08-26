<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->infolist }}

        @if (!empty($recentInvoices))
            <x-filament::section heading="Dernières factures">
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($recentInvoices as $invoice)
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-document-text class="w-5 h-5 text-gray-400" />
                                <div>
                                    <a href="{{ url('/customer/customer-invoices/' . $invoice['id']) }}"
                                       class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $invoice['reference'] }}
                                    </a>
                                    @if ($invoice['is_overdue'])
                                        <span class="ml-2 text-xs font-medium text-danger-600 dark:text-danger-400">En retard</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($invoice['total_ttc'], 2, ',', ' ') }} €
                                </span>
                                <span class="inline-flex items-center rounded-md bg-{{ $invoice['status_color'] }}-50 px-2 py-1 text-xs font-medium text-{{ $invoice['status_color'] }}-700 ring-1 ring-inset ring-{{ $invoice['status_color'] }}-600/20 dark:bg-{{ $invoice['status_color'] }}-500/10 dark:text-{{ $invoice['status_color'] }}-400 dark:ring-{{ $invoice['status_color'] }}-500/30">
                                    {{ $invoice['status'] }}
                                </span>
                                @if ($invoice['due_date'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Échéance : {{ $invoice['due_date'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
