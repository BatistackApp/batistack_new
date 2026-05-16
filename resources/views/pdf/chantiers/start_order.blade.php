@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-center mb-8 border-b-4 border-blue-batistack pb-4">
        <div>
            <h1 class="text-3xl font-bold text-blue-batistack uppercase">Ordre de Service</h1>
            <p class="text-xl text-slate-600 font-medium">Référence : {{ $chantier->reference }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm font-bold uppercase">Lancement de Chantier</p>
            <p class="text-xs text-slate-500 italic">Généré le {{ $generated_at }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8">
        <!-- INFORMATIONS CHANTIER -->
        <section class="bg-gray-header p-5 rounded-2xl border border-slate-200">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-slate-300 pb-2">Détails du Projet</h2>
            <table class="text-[10px] w-full">
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Désignation :</td>
                    <td class="text-right font-bold">{{ $chantier->name }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Client :</td>
                    <td class="text-right">{{ $chantier->client->name }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Adresse :</td>
                    <td class="text-right">{{ $chantier->address }}, {{ $chantier->zip_code }} {{ $chantier->city }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Démarrage prévu :</td>
                    <td class="text-right font-bold">{{ $chantier->start_date_preview?->format('d/m/Y') ?? 'À définir' }}</td>
                </tr>
            </table>
        </section>

        <!-- ENCADREMENT -->
        <section class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-blue-200 pb-2">Responsable de Site</h2>
            @if($chantier->manager)
                <div class="flex items-center space-x-4">
                    <div>
                        <p class="text-lg font-bold text-blue-900">{{ $chantier->manager->full_name }}</p>
                        <p class="text-sm text-blue-700 italic">Conducteur de Travaux</p>
                        <p class="text-xs mt-2">📞 {{ $chantier->manager->phone ?? 'N/A' }}</p>
                        <p class="text-xs">✉️ {{ $chantier->manager->email }}</p>
                    </div>
                </div>
            @else
                <p class="text-red-500 italic text-xs">Aucun responsable assigné.</p>
            @endif
        </section>
    </div>

    <!-- ÉQUIPE ASSIGNÉE -->
    <div class="mb-8">
        <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-4 uppercase">Composition de l'Équipe</h2>
        <table class="text-[10px]">
            <thead>
            <tr class="bg-slate-100 text-slate-700">
                <th class="text-left py-2 px-3">Matricule</th>
                <th class="text-left">Nom du Collaborateur</th>
                <th class="text-left">Qualification / Poste</th>
                <th class="text-center">Habilitations Valides</th>
            </tr>
            </thead>
            <tbody>
            @forelse($chantier->members as $member)
                <tr>
                    <td class="py-2 px-3 font-mono">{{ $member->registration_number }}</td>
                    <td class="font-bold">{{ $member->full_name }}</td>
                    <td>{{ $member->currentContract?->job_title ?? 'Ouvrier' }}</td>
                    <td class="text-center">
                        @php $compliance = app(\App\Services\Chantiers\ChantierComplianceService::class)->checkTeamCompliance($chantier); @endphp
                        <span class="text-green-600 font-bold text-[8px]">CONFORME</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 italic text-slate-400 text-center">Aucune équipe assignée pour le moment.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 text-[10px] text-justify">
        <p class="font-bold uppercase mb-2">Instructions de Sécurité :</p>
        <p>Le personnel doit obligatoirement porter ses Équipements de Protection Individuelle (EPI). Tout incident ou anomalie sur le site doit être immédiatement rapporté au conducteur de travaux via le journal de bord numérique Batistack.</p>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[8px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Document interne Batistack - Ordre de Service n°{{ $chantier->reference }}</div>
        <div class="font-bold uppercase tracking-widest">Batistack OS v1.0</div>
    </footer>
@endsection
