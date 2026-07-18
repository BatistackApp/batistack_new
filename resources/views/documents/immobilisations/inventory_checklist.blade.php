<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inventaire Chantier - {{ $chantier->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 0; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body class="bg-white p-10 text-gray-800">

    <div class="flex justify-between items-end border-b-2 border-indigo-500 pb-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-indigo-700">Fiche de Récolement Matériel</h1>
            <h2 class="text-xl font-semibold text-gray-700 mt-1">Chantier : {{ $chantier->name }} (Réf: {{ $chantier->reference }})</h2>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p>Généré le : {{ now()->format('d/m/Y à H:i') }}</p>
            <p>Responsable : {{ $chantier->manager ? $chantier->manager->first_name . ' ' . $chantier->manager->last_name : 'Non assigné' }}</p>
        </div>
    </div>

    <p class="mb-6 text-gray-600 bg-gray-50 p-4 border rounded">
        <strong>Instructions :</strong> Veuillez cocher la case <code>[Vérifié]</code> si le matériel est bien présent sur site. 
        Notez toute observation (dégradation, panne) dans la colonne "Observations". Ce document doit être signé et retourné.
    </p>

    <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
        <thead class="bg-indigo-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-center w-16 text-xs font-medium text-indigo-900 uppercase">Vérifié</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-indigo-900 uppercase">Matériel / Machine</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-indigo-900 uppercase">Catégorie</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-indigo-900 uppercase">N° Série</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-indigo-900 uppercase">Observations</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($assets as $asset)
            <tr>
                <td class="px-4 py-4 text-center">
                    <div class="w-6 h-6 border-2 border-gray-400 rounded mx-auto"></div>
                </td>
                <td class="px-4 py-4 text-sm font-bold text-gray-900">{{ $asset->name }}</td>
                <td class="px-4 py-4 text-sm text-gray-600">{{ $asset->category->name }}</td>
                <td class="px-4 py-4 text-sm text-gray-500">{{ $asset->serial_number ?: '-' }}</td>
                <td class="px-4 py-4">
                    <div class="w-full border-b border-gray-300 border-dashed h-4"></div>
                    <div class="w-full border-b border-gray-300 border-dashed h-4 mt-2"></div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">Aucun matériel immobilisé n'est actuellement affecté à ce chantier.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-12 flex justify-end">
        <div class="text-center w-64">
            <p class="font-bold text-gray-700 mb-20 text-left">Signature du Responsable Chantier :</p>
            <div class="w-full h-px bg-gray-400"></div>
            <p class="text-xs text-gray-500 mt-2 text-left">Date : ____ / ____ / ________</p>
        </div>
    </div>

</body>
</html>
