<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État Global des Dotations - {{ $year }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 0; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body class="bg-white p-10 text-gray-800">

    <div class="border-b-2 border-indigo-500 pb-6 mb-8 text-center">
        <h1 class="text-3xl font-bold text-indigo-700">État des Dotations aux Amortissements</h1>
        <p class="text-gray-500 mt-2 text-xl">Exercice : <strong>{{ $year }}</strong></p>
        <p class="text-gray-400 text-sm mt-1">Imprimé le {{ now()->format('d/m/Y') }}</p>
    </div>

    @php
        $grandTotalBrut = 0;
        $grandTotalDotation = 0;
        $grandTotalVnc = 0;
    @endphp

    @foreach($categories as $category)
        @if($category->fixedAssets->count() > 0)
        
        @php
            $catBrut = 0;
            $catDotation = 0;
            $catVnc = 0;
        @endphp

        <h2 class="text-xl font-bold text-gray-800 mb-2 mt-8">{{ $category->name }} (Compte: {{ $category->account_code ?: 'N/A' }})</h2>
        <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden mb-6">
            <thead class="bg-indigo-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-indigo-900 uppercase">Actif</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-indigo-900 uppercase">Date Achat</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-indigo-900 uppercase">Valeur Brute</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-indigo-900 uppercase">Dotation {{ $year }}</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-indigo-900 uppercase">VNC Fin {{ $year }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($category->fixedAssets as $asset)
                    @php
                        $depreciation = $asset->depreciations->firstWhere(function ($d) use ($year) {
                            return \Carbon\Carbon::parse($d->period_date)->year == $year;
                        });
                        
                        $dotation = $depreciation ? $depreciation->amount : 0;
                        $vnc = $depreciation ? $depreciation->remaining_vnc : 0;
                        
                        $catBrut += $asset->purchase_price;
                        $catDotation += $dotation;
                        $catVnc += $vnc;
                    @endphp
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $asset->name }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $asset->purchase_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ number_format($asset->purchase_price, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">{{ number_format($dotation, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-500">{{ number_format($vnc, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
                <!-- Sous-total catégorie -->
                <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                    <td colspan="2" class="px-4 py-2 text-right text-sm text-gray-700 uppercase">Sous-total {{ $category->name }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ number_format($catBrut, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-2 text-sm text-right text-indigo-700">{{ number_format($catDotation, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ number_format($catVnc, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>

        @php
            $grandTotalBrut += $catBrut;
            $grandTotalDotation += $catDotation;
            $grandTotalVnc += $catVnc;
        @endphp

        @endif
    @endforeach

    <!-- Total Général -->
    <div class="mt-10 bg-indigo-600 text-white p-6 rounded-lg flex justify-between items-center shadow-md">
        <h2 class="text-2xl font-bold uppercase tracking-wider">Total Général ({{ $year }})</h2>
        <div class="text-right space-y-1">
            <p class="text-indigo-100">Valeur Brute Globale : <span class="font-bold text-white ml-2">{{ number_format($grandTotalBrut, 2, ',', ' ') }} €</span></p>
            <p class="text-indigo-100 text-xl mt-2 border-t border-indigo-400 pt-2">Dotation Totale Exercice : <span class="font-bold text-yellow-300 ml-2">{{ number_format($grandTotalDotation, 2, ',', ' ') }} €</span></p>
            <p class="text-indigo-100">VNC Globale Restante : <span class="font-bold text-white ml-2">{{ number_format($grandTotalVnc, 2, ',', ' ') }} €</span></p>
        </div>
    </div>

</body>
</html>
