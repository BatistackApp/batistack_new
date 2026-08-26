<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-folder-open"
        heading="Mes Documents"
    >
        @php
            $payslips = $this->getPayslips();
            $rhDocs = $this->getRhDocuments();
            $total = $this->getDocumentsTotal();
        @endphp

        {{-- Payslips --}}
        @if($payslips->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Bulletins de Paie</h3>
                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($payslips as $payslip)
                        <div class="flex items-center justify-between py-2 px-1">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-document-text class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Bulletin {{ $payslip->period }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @if($payslip->net_paid)
                                            {{ number_format($payslip->net_paid, 2, ',', ' ') }} €
                                        @endif
                                        @if($payslip->payment_date)
                                            — virement le {{ $payslip->payment_date->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($payslip->digiposte_status === 'deposited')
                                    <x-filament::badge color="success" size="sm">Digiposte</x-filament::badge>
                                @endif
                                <a href="{{ Storage::url($payslip->pdf_path) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    PDF
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- RH Documents --}}
        @if($rhDocs->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Documents RH</h3>
                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($rhDocs as $doc)
                        <div class="flex items-center justify-between py-2 px-1">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-paper-clip class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $doc->file_name }}
                                        @if($doc->size)
                                            — {{ \App\Filament\Salarie\Widgets\DigiposteWidget::formatSize($doc->size) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ Storage::url($doc->getPath()) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                Télécharger
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if($payslips->isEmpty() && $rhDocs->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-folder-open class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                <p class="text-sm">Aucun document disponible pour le moment.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
