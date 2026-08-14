<div class="space-y-4">
    @if (empty($requirements))
        <div class="fi-wi p-6 text-sm text-gray-500 dark:text-gray-400">
            Aucun article en rupture : le stock actuel (physique + en commande) couvre les quantitatifs de la maquette.
        </div>
    @else
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="px-3 py-2 font-medium">Article</th>
                    <th class="px-3 py-2 font-medium text-right">Besoin brut</th>
                    <th class="px-3 py-2 font-medium text-right">Stock dispo.</th>
                    <th class="px-3 py-2 font-medium text-right">En commande</th>
                    <th class="px-3 py-2 font-medium text-right">À commander</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requirements as $requirement)
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <td class="px-3 py-2">{{ $requirement['item']->name }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($requirement['quantity_required'], 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($requirement['physical_stock'], 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($requirement['pending_order_stock'], 2) }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ number_format($requirement['quantity_to_order'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>