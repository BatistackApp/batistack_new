<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche Immobilisation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 0; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body class="bg-white p-10 text-gray-800">

    <div class="flex justify-between items-start border-b-2 border-indigo-500 pb-6 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-indigo-700">Fiche d'Immobilisation</h1>
            <p class="text-gray-500 mt-2">Réf. Actif: #{{ $asset->id }} - Imprimé le {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="w-32 h-32 border p-2 rounded-lg shadow-sm bg-gray-50 flex items-center justify-center">
            <img src="{{ $qrCode }}" alt="QR Code" class="w-full h-full object-contain">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-10">
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2">Informations Générales</h2>
            <ul class="space-y-3">
                <li><span class="font-semibold text-gray-600">Nom :</span> {{ $asset->name }}</li>
                <li><span class="font-semibold text-gray-600">Catégorie :</span> {{ $asset->category->name }}</li>
                <li><span class="font-semibold text-gray-600">N° Série :</span> {{ $asset->serial_number ?: 'N/A' }}</li>
                <li><span class="font-semibold text-gray-600">Statut :</span> {{ $asset->status->getLabel() }}</li>
            </ul>
        </div>
        <div class="bg-indigo-50 p-6 rounded-lg shadow-sm">
            <h2 class="text-xl font-bold text-indigo-700 mb-4 border-b border-indigo-200 pb-2">Données Comptables</h2>
            <ul class="space-y-3">
                <li><span class="font-semibold text-indigo-900">Date d'achat :</span> {{ $asset->purchase_date->format('d/m/Y') }}</li>
                <li><span class="font-semibold text-indigo-900">Valeur brute :</span> {{ number_format($asset->purchase_price, 2, ',', ' ') }} €</li>
                <li><span class="font-semibold text-indigo-900">Valeur résiduelle :</span> {{ number_format($asset->salvage_value, 2, ',', ' ') }} €</li>
                <li><span class="font-semibold text-indigo-900">Méthode :</span> {{ $asset->depreciation_method->getLabel() }} sur {{ $asset->useful_life_years }} ans</li>
                @if($asset->grant_amount > 0)
                <li><span class="font-semibold text-indigo-900">Subvention ({{ $asset->grant_name ?? 'N/A' }}) :</span> {{ number_format($asset->grant_amount, 2, ',', ' ') }} €</li>
                @endif
            </ul>
        </div>
    </div>

    @if($asset->chantier || $asset->vehicle)
    <div class="grid grid-cols-{{ ($asset->chantier && $asset->vehicle) ? '2' : '1' }} gap-8 mb-10">
        @if($asset->chantier)
        <div class="bg-green-50 p-6 rounded-lg shadow-sm border border-green-200">
            <h2 class="text-xl font-bold text-green-800 mb-4 border-b border-green-200 pb-2">Affectation Chantier</h2>
            <ul class="space-y-3">
                <li><span class="font-semibold text-green-900">Nom :</span> {{ $asset->chantier->name }}</li>
                <li><span class="font-semibold text-green-900">Référence :</span> {{ $asset->chantier->reference ?: 'N/A' }}</li>
                @if($asset->chantier->manager)
                <li><span class="font-semibold text-green-900">Responsable :</span> {{ $asset->chantier->manager->first_name }} {{ $asset->chantier->manager->last_name }}</li>
                @endif
            </ul>
        </div>
        @endif

        @if($asset->vehicle)
        <div class="bg-orange-50 p-6 rounded-lg shadow-sm border border-orange-200">
            <h2 class="text-xl font-bold text-orange-800 mb-4 border-b border-orange-200 pb-2">Liaison Véhicule</h2>
            <ul class="space-y-3">
                <li><span class="font-semibold text-orange-900">Plaque d'immatriculation :</span> {{ $asset->vehicle->license_plate }}</li>
                <li><span class="font-semibold text-orange-900">Marque / Modèle :</span> {{ $asset->vehicle->brand }} {{ $asset->vehicle->model }}</li>
                @if($asset->vehicle->status)
                <li><span class="font-semibold text-orange-900">Statut du véhicule :</span> {{ $asset->vehicle->status->getLabel() }}</li>
                @endif
            </ul>
        </div>
        @endif
    </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-700 mb-4 border-b-2 border-gray-200 pb-2">Tableau d'Amortissement</h2>
    
    @php
        $hasGrant = $asset->grant_amount > 0;
    @endphp

    <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date (Période)</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Dotation (€)</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">VNC Restante (€)</th>
                @if($hasGrant)
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reprise Subv. (€)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subv. Restante (€)</th>
                @endif
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($asset->depreciations as $depreciation)
            <tr class="{{ $depreciation->is_passed ? 'bg-gray-50 opacity-75' : '' }}">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $depreciation->period_date->format('d/m/Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-700">{{ number_format($depreciation->amount, 2, ',', ' ') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ number_format($depreciation->remaining_vnc, 2, ',', ' ') }}</td>
                @if($hasGrant)
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ number_format($depreciation->grant_reversal_amount, 2, ',', ' ') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ number_format($depreciation->grant_remaining_amount, 2, ',', ' ') }}</td>
                @endif
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                    @if($depreciation->is_passed)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Passée</span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Prévision</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
