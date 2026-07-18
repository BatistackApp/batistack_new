<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV de Cession / Mise au Rebut</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 0; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body class="bg-white p-12 text-gray-800">

    <div class="text-center pb-8 border-b-2 border-red-500 mb-10">
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-widest">Procès-Verbal de Sortie d'Actif</h1>
        <p class="text-red-600 mt-2 text-lg font-semibold">Attestation de Cession / Mise au Rebut</p>
    </div>

    <p class="mb-6 text-justify leading-relaxed text-gray-700">
        Je soussigné, représentant légal de l'entreprise, certifie par la présente que l'immobilisation désignée ci-dessous a été sortie de notre parc actif en date du <strong>{{ now()->format('d/m/Y') }}</strong>.
    </p>

    <div class="bg-gray-50 border border-gray-200 p-6 rounded-lg mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-300 pb-2">Désignation de l'actif</h2>
        <div class="grid grid-cols-2 gap-y-4">
            <div><span class="font-semibold text-gray-600 text-sm uppercase">Nom de l'actif :</span><br> <span class="text-lg">{{ $asset->name }}</span></div>
            <div><span class="font-semibold text-gray-600 text-sm uppercase">Catégorie :</span><br> <span class="text-lg">{{ $asset->category->name }}</span></div>
            <div><span class="font-semibold text-gray-600 text-sm uppercase">Numéro de série :</span><br> <span class="text-lg">{{ $asset->serial_number ?: 'Non spécifié' }}</span></div>
            <div><span class="font-semibold text-gray-600 text-sm uppercase">Date d'acquisition :</span><br> <span class="text-lg">{{ $asset->purchase_date->format('d/m/Y') }}</span></div>
        </div>
    </div>

    @php
        // On récupère la dernière dotation pour avoir la VNC actuelle
        $lastDepreciation = $asset->depreciations()->where('is_passed', true)->orderByDesc('period_date')->first();
        $vnc = $lastDepreciation ? $lastDepreciation->remaining_vnc : ($asset->purchase_price - $asset->salvage_value);
    @endphp

    <div class="bg-red-50 border border-red-100 p-6 rounded-lg mb-12">
        <h2 class="text-lg font-bold text-red-800 mb-4 border-b border-red-200 pb-2">Conditions de sortie</h2>
        <div class="grid grid-cols-2 gap-y-6">
            <div><span class="font-semibold text-red-900 text-sm uppercase">Valeur Brute d'achat :</span><br> <span class="text-xl font-bold">{{ number_format($asset->purchase_price, 2, ',', ' ') }} €</span></div>
            <div><span class="font-semibold text-red-900 text-sm uppercase">Valeur Nette Comptable (VNC) :</span><br> <span class="text-xl font-bold">{{ number_format($vnc, 2, ',', ' ') }} €</span></div>
            
            <div class="col-span-2 pt-4 border-t border-red-200">
                <span class="font-semibold text-red-900 text-sm uppercase">Motif de la sortie :</span>
                <p class="mt-2 p-4 bg-white border border-red-100 rounded text-gray-800">
                    Cession ou rebut acté(e). <!-- Ideally we would pass reason and sale_price from the action, but this is a generic certificate of the current state. Wait, the action disposed of it, so we might not have the explicit reason unless saved on the model. -->
                </p>
            </div>
        </div>
    </div>

    <div class="mt-16 flex justify-between">
        <div class="text-center">
            <p class="font-bold text-gray-700 mb-16">Signature du Responsable Matériel</p>
            <div class="w-48 h-px bg-gray-400 mx-auto"></div>
        </div>
        <div class="text-center">
            <p class="font-bold text-gray-700 mb-16">Signature de la Direction / Comptabilité</p>
            <div class="w-48 h-px bg-gray-400 mx-auto"></div>
        </div>
    </div>

</body>
</html>
