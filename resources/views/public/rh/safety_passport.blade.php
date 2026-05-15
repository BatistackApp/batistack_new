<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Sécurité - Batistack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen pb-10">
<!-- Header Entreprise -->
<div class="bg-[#002157] text-white p-6 shadow-lg text-center">
    <h1 class="text-xl font-bold uppercase tracking-wider">Passeport Sécurité Numérique</h1>
    <p class="text-xs opacity-75 mt-1">{{ $company->legal_name }}</p>
</div>

<div class="max-w-md mx-auto -mt-4 px-4">
    <!-- Carte Identité -->
    <div class="bg-white rounded-2xl shadow-xl p-6 mb-6 border-t-4 border-blue-600">
        <div class="flex items-center space-x-4">
            <div class="h-20 w-20 rounded-full bg-slate-200 overflow-hidden border-2 border-slate-100">
                @if($employee->hasMedia('avatar'))
                    <img src="{{ $employee->getFirstMediaUrl('avatar') }}" class="object-cover h-full w-full">
                @else
                    <div class="flex items-center justify-center h-full text-slate-400 text-3xl">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $employee->full_name }}</h2>
                <p class="text-sm text-slate-500">Matricule : {{ $employee->registration_number }}</p>
                <p class="text-xs font-semibold uppercase text-blue-600 mt-1">{{ $employee->currentContract?->job_title ?? 'Salarié en poste' }}</p>
            </div>
        </div>
    </div>

    <!-- Statut Médical -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="font-bold text-slate-800"><i class="fa-solid fa-heart-pulse mr-2 text-red-500"></i> Aptitude Médicale</h3>
            <span class="text-[10px] text-slate-400 uppercase font-bold">Dernière VIP/SIR</span>
        </div>

        @if($lastMedicalVisit)
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">{{ $lastMedicalVisit->type->getLabel() }}</p>
                    <p class="text-xs text-slate-500 italic">Échéance : {{ $lastMedicalVisit->next_due_date->format('d/m/Y') }}</p>
                </div>
                <div @class([
                        'px-3 py-1 rounded-full text-xs font-bold uppercase',
                        'bg-green-100 text-green-700' => !$lastMedicalVisit->isExpired(),
                        'bg-red-100 text-red-700' => $lastMedicalVisit->isExpired(),
                    ])>
                    {{ $lastMedicalVisit->isExpired() ? 'Expirée' : 'Valide' }}
                </div>
            </div>
        @else
            <p class="text-sm text-red-500 italic">Aucune donnée médicale enregistrée.</p>
        @endif
    </div>

    <!-- Habilitations Actives -->
    <div class="bg-white rounded-2xl shadow-md p-6">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="font-bold text-slate-800"><i class="fa-solid fa-shield-check mr-2 text-green-500"></i> Habilitations & CACES</h3>
            <span class="text-xs font-bold text-blue-600">{{ $activeQualifications->count() }}</span>
        </div>

        <div class="space-y-4">
            @forelse($activeQualifications as $qualif)
                <div class="flex items-start space-x-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <div class="text-blue-500 mt-1 text-lg">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-slate-800">{{ $qualif->label }}</p>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-[10px] text-slate-500">N° {{ $qualif->reference_number ?? 'N/A' }}</p>
                            <p class="text-[10px] font-bold text-slate-700">Expire le {{ $qualif->expires_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-slate-400 py-4 italic">Aucune habilitation active trouvée.</p>
            @endforelse
        </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-[10px] text-slate-400 mt-8">
        Vérification certifiée par Batistack ERP<br>
        Horodatage du contrôle : {{ now()->format('d/m/Y H:i') }}
    </p>
</div>
</body>
</html>
