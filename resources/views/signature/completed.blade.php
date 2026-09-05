<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Complétée</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
        @if(isset($signer) && $signer->status->value === 'refused')
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Signature Refusée</h1>
            <p class="text-gray-600 mb-4">Vous avez refusé de signer ce document.</p>
            <p class="text-sm text-gray-400">Le donneur d'ordre a été notifié.</p>
        @else
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Document Signé</h1>
            <p class="text-gray-600 mb-6">Ce document a été signé électroniquement et crypté. L'émetteur a été notifié.</p>

            @if(isset($allSigners) && $allSigners->count() > 0)
                <div class="text-left bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Statut des signataires</h2>
                    <div class="space-y-2">
                        @foreach($allSigners as $s)
                            <div class="flex items-center gap-3 text-sm">
                                @if($s->status->value === 'signed')
                                    <span class="w-5 h-5 rounded-full bg-green-500 text-white flex items-center justify-center text-xs flex-shrink-0">✓</span>
                                    <span class="font-medium text-green-700">{{ $s->name }}</span>
                                    <span class="text-gray-400 text-xs ml-auto">{{ $s->signed_at ? $s->signed_at->format('d/m/Y H:i') : '' }}</span>
                                @elseif($s->status->value === 'refused')
                                    <span class="w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs flex-shrink-0">✗</span>
                                    <span class="font-medium text-red-700">{{ $s->name }}</span>
                                    <span class="text-red-500 text-xs ml-auto">Refusé</span>
                                @else
                                    <span class="w-5 h-5 rounded-full bg-yellow-400 text-white flex items-center justify-center text-xs flex-shrink-0">⏳</span>
                                    <span class="font-medium text-yellow-700">{{ $s->name }}</span>
                                    <span class="text-yellow-600 text-xs ml-auto">En attente</span>
                                @endif
                                <span class="text-gray-400 text-xs">— {{ $s->role }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-sm text-gray-400">Batistack - Gestion de conformité sécurisée</p>
        @endif
    </div>
</body>
</html>
