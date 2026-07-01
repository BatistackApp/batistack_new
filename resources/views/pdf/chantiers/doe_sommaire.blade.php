@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-12 border-b-4 border-blue-batistack pb-6">
        <div>
            <h1 class="text-3xl font-bold text-blue-batistack uppercase mb-1">Dossier des Ouvrages Exécutés</h1>
            <p class="text-slate-500 font-bold text-lg italic">Sommaire et Table des Pièces Constitutionnelles</p>
        </div>
        <div class="text-right">
            <span class="font-mono text-xs bg-slate-100 px-3 py-1 rounded font-bold">{{ $chantier->reference }}</span>
            <p class="text-[9px] text-slate-400 mt-2">Généré par Batistack le {{ $generated_at }}</p>
        </div>
    </div>

    <!-- CONTEXTE DU CHANTIER -->
    <div class="grid grid-cols-2 gap-8 mb-10">
        <div class="bg-gray-header p-5 rounded-xl border border-slate-200 text-[10px]">
            <h3 class="font-bold text-blue-batistack uppercase mb-2">Données de l'Ouvrage</h3>
            <p class="mb-1"><strong>Projet :</strong> {{ $chantier->name }}</p>
            <p class="mb-1"><strong>Client (Maître d'Ouvrage) :</strong> {{ $chantier->client->name }}</p>
            <p class="mb-1"><strong>Adresse :</strong> {{ $chantier->full_address }}</p>
        </div>
        <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100 text-[10px]">
            <h3 class="font-bold text-blue-batistack uppercase mb-2">Maîtrise d'Œuvre</h3>
            <p class="mb-1"><strong>Entreprise Principale :</strong> {{ $company->legal_name }}</p>
            <p class="mb-1"><strong>Conducteur de Travaux :</strong> {{ $chantier->manager?->full_name ?? 'Non spécifié' }}</p>
            <p class="mb-1"><strong>Date de Réception :</strong> {{ $chantier->end_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- NOMENCLATURE DES PIÈCES -->
    <div class="mb-12">
        <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Inventaire des Documents Remis</h2>

        <table class="text-[10px]">
            <thead>
            <tr class="bg-slate-100 text-slate-700">
                <th class="py-2 px-3 text-left w-12">N°</th>
                <th class="text-left">Catégorie / Classement</th>
                <th class="text-left">Intitulé du Document</th>
            </tr>
            </thead>
            <tbody>
            @foreach($documents as $index => $doc)
                <tr>
                    <td class="py-2 px-3 font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="font-bold text-blue-900 uppercase text-[9px]">{{ $doc->category }}</td>
                    <td>{{ $doc->name }}</td>
                </tr>
            @endforeach
            @foreach($itemsWithSheets as $index => $item)
                <tr>
                    <td class="py-2 px-3 font-mono">{{ str_pad(count($documents) + $index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="font-bold text-indigo-900 uppercase text-[9px]">FICHE_TECHNIQUE</td>
                    <td>Fiche technique : {{ $item->name }} (Réf: {{ $item->reference }})</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 text-[9px] text-justify leading-relaxed">
        <p class="font-bold uppercase mb-1">Notice Légale de Garantie :</p>
        <p>Le présent Dossier des Ouvrages Exécutés (DOE) est remis au Maître d'Ouvrage conformément aux dispositions de l'article R. 2196-1 du Code de la commande publique. Il contient l'ensemble des notices, plans de récolement et documentations d'entretien nécessaires à la conservation et à l'exploitation des ouvrages livrés.</p>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[7px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div> Dossier Certifié Conforme - {{ $company->legal_name }} </div>
        <div class="font-bold uppercase">Batistack DOE Summary v1.0</div>
    </footer>
@endsection
