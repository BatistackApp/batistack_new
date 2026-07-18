<div class="fi-in-view-entry">
    <div class="fi-in-view-entry-content">
        <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
            <thead>
                <tr>
                    <th class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white">Période</th>
                    <th class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white">Montant Dotation</th>
                    <th class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white">VNC Restante</th>
                    <th class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($getRecord()->depreciations as $depreciation)
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($depreciation->period_date)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($depreciation->amount, 2, ',', ' ') }} €
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($depreciation->remaining_vnc, 2, ',', ' ') }} €
                        </td>
                        <td class="px-3 py-2 text-sm">
                            @if($depreciation->is_passed)
                                <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">Passée</span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Prévisionnelle</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                            Aucun plan d'amortissement généré.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
