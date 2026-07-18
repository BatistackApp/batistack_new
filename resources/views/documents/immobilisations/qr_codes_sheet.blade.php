<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Plaquette QR Codes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 1cm; size: A4; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; background-color: white; }
        .page-break { page-break-after: always; }
        /* 3 colonnes, environ 21 étiquettes par page (7 rangées de 3) */
    </style>
</head>
<body class="p-4">

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-800">Plaquette d'Étiquettes QR Codes</h1>
        <p class="text-gray-500 text-sm">Généré le {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="grid grid-cols-3 gap-4">
        @foreach($assets as $asset)
            <div class="border-2 border-dashed border-gray-400 p-4 rounded-lg flex flex-col items-center text-center">
                <!-- Nom de la machine -->
                <p class="font-bold text-gray-900 text-sm uppercase mb-1 h-10 overflow-hidden">{{ \Illuminate\Support\Str::limit($asset->name, 40) }}</p>
                
                <!-- QR Code Image -->
                <div class="w-32 h-32 mb-2 bg-white flex items-center justify-center">
                    <img src="{{ $qrCodes[$asset->id] }}" alt="QR Code" class="max-w-full max-h-full">
                </div>

                <!-- Footer de l'étiquette -->
                <p class="text-xs text-gray-600 font-semibold mb-1">Réf: #{{ $asset->id }} - {{ $asset->category->name }}</p>
                <p class="text-[10px] text-gray-400">Batistack ERP</p>
            </div>
            
            <!-- Gestion des sauts de page (21 étiquettes par page) -->
            @if($loop->iteration % 21 == 0 && !$loop->last)
                </div>
                <div class="page-break"></div>
                <div class="grid grid-cols-3 gap-4 mt-8">
            @endif
        @endforeach
    </div>

</body>
</html>
