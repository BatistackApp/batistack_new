@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-center mb-8 border-b-4 border-blue-batistack pb-4">
        <div>
            <h1 class="text-3xl font-bold text-blue-batistack uppercase">Mise à Disposition</h1>
            <p class="text-xl text-slate-600 font-medium">Référence d'affectation : #{{ $assignment->id }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm font-bold uppercase">Remise des Clés & Responsabilités</p>
            <p class="text-xs text-slate-500 italic">Édité le {{ $generated_at }}</p>
        </div>
    </div>

    <!-- RÉSUMÉ DE L'AFFECTATION -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <!-- COLLABORATEUR DÉPOSITAIRE -->
        <section class="bg-gray-header p-5 rounded-2xl border border-slate-200">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-slate-300 pb-2">Dépositaire Juridique</h2>
            <div class="text-[10px] space-y-2">
                <p class="text-base font-bold text-slate-800">{{ $assignment->employee->full_name }}</p>
                <p><strong>Matricule :</strong> {{ $assignment->employee->registration_number }}</p>
                <p><strong>Poste :</strong> {{ $assignment->employee->currentContract?->job_title ?? 'Salarié' }}</p>
                <p><strong>Téléphone :</strong> {{ $assignment->employee->phone ?? 'N/A' }}</p>
            </div>
        </section>

        <!-- VÉHICULE CONFIÉ -->
        <section class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-blue-200 pb-2">Détails du Véhicule</h2>
            <div class="text-[10px] space-y-2">
                <p class="text-base font-bold text-blue-900">{{ $assignment->vehicle->brand }} {{ $assignment->vehicle->model }}</p>
                <p><strong>Immatriculation :</strong> <span class="font-mono bg-white px-2 py-0.5 border rounded text-xs font-bold">{{ $assignment->vehicle->license_plate }}</span></p>
                <p><strong>Code Interne :</strong> {{ $assignment->vehicle->reference }}</p>
                <p>
                    <strong>Type d'actif :</strong> {{ $assignment->vehicle->type->getLabel() }}
                    @if($assignment->vehicle->crit_air_level)
                        <span class="ml-2 px-2 py-0.5 bg-orange-100 text-orange-800 rounded font-bold text-[8px]">Crit'Air {{ $assignment->vehicle->crit_air_level }}</span>
                    @endif
                </p>
            </div>
        </section>
    </div>

    <!-- CONTEXTE OPÉRATIONNEL -->
    <div class="mb-8">
        <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-4 uppercase">Conditions & États Initiaux</h2>
        <table class="text-[10px] w-full border border-slate-200">
            <tbody>
            <tr class="h-10 bg-slate-50">
                <td class="font-bold px-3">Date et Heure de Départ :</td>
                <td class="text-right px-3">{{ $assignment->started_at->format('d/m/Y H:i') }}</td>
                <td class="font-bold px-3 border-l">
                    @if($assignment->vehicle->usage_unit === 'hours')
                        Heures au Départ (Horamètre) :
                    @else
                        Kilométrage au Départ :
                    @endif
                </td>
                <td class="text-right px-3 font-mono font-bold">
                    {{ number_format($assignment->start_odometer, 1, ',', ' ') }}
                    {{ $assignment->vehicle->usage_unit === 'hours' ? 'h' : 'km' }}
                </td>
            </tr>
            <tr class="h-10">
                <td class="font-bold px-3">Chantier d'Imputation :</td>
                <td class="text-right px-3">{{ $assignment->chantier ? $assignment->chantier->name . ' (' . $assignment->chantier->reference . ')' : 'Usage Général / Logistique dépôt' }}</td>
                <td class="font-bold px-3 border-l">Destination / Motif :</td>
                <td class="text-right px-3 italic">{{ $assignment->purpose ?? 'Non spécifié' }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- INVENTAIRE DE L'OUTILLAGE EMBARQUÉ DE VALEUR -->
    @if($assignment->vehicle->inventories && $assignment->vehicle->inventories->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-3 uppercase">Inventaire de l'Outillage Embarqué de Valeur</h2>
            <table class="text-[9px] w-full border border-slate-200">
                <thead>
                <tr class="bg-slate-100 font-bold text-slate-700">
                    <th class="text-left px-3 py-2 border-b">Équipement / Outillage</th>
                    <th class="text-left px-3 py-2 border-b">Référence Article</th>
                    <th class="text-left px-3 py-2 border-b">Numéro de Série / Tag Unique</th>
                    <th class="text-center px-3 py-2 border-b w-24">Quantité</th>
                </tr>
                </thead>
                <tbody>
                @foreach($assignment->vehicle->inventories as $inventory)
                    <tr class="border-b last:border-b-0">
                        <td class="px-3 py-2 font-bold text-slate-800">{{ $inventory->item->name }}</td>
                        <td class="px-3 py-2 font-mono text-slate-500">{{ $inventory->item->reference }}</td>
                        <td class="px-3 py-2 font-mono text-slate-600">{{ $inventory->serial_number ?? 'Non renseigné' }}</td>
                        <td class="text-center px-3 py-2 font-bold">{{ $inventory->quantity }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="text-[7px] text-slate-400 mt-1 italic">Veuillez vérifier la présence effective de chaque outil avant le départ du dépôt. Tout matériel manquant à la restitution sera imputé.</p>
        </div>
    @endif

    <!-- CADRE JURIDIQUE ET CLAUSES -->
    <div class="p-6 border border-slate-300 rounded-xl bg-slate-50 text-[9px] text-justify leading-relaxed mb-10">
        <p class="font-bold uppercase text-slate-800 mb-2">Clauses de Garde Juridique et Transfert de Responsabilités :</p>
        <p class="mb-2">Le salarié soussigné reconnaît avoir reçu les clés, les documents de bord, ainsi que l'ensemble du matériel et outillage recensé dans l'inventaire embarqué en parfait état de marche, de propreté et de sécurité. Le présent document formalise le transfert de la garde juridique du véhicule et de son contenu conformément aux articles 1242 et suivants du Code civil.</p>
        <p class="mb-2"><strong>Infractions au Code de la Route :</strong> En application de l'article L. 121-6 du Code de la route, l'entreprise est légalement tenue d'indiquer l'identité du conducteur responsable de toute infraction commise avec ce véhicule durant toute la période d'affectation. Le salarié s'engage à supporter les conséquences financières et les retraits de points relatifs aux contraventions reçues pendant son temps de garde.</p>
        <p><strong>Utilisation :</strong> L'utilisation du véhicule est strictement réservée aux besoins professionnels liés à l'activité de Batistack et des chantiers imputés. Sauf accord écrit explicite, l'usage à titre privé est formellement interdit.</p>
    </div>

    <!-- ACCORD ET SIGNATURES -->
    <div class="mt-16 flex justify-between items-end">
        <div class="text-center w-5/12 border border-slate-200 p-4 rounded-xl bg-white shadow-sm">
            <p class="font-bold text-[10px] uppercase text-slate-600 mb-20">Le Collaborateur Dépositaire</p>
            <p class="text-[8px] text-slate-400 italic">Signature précédée de la mention "Lu et approuvé"</p>
        </div>
        <div class="text-center w-2/12 text-[8px] text-slate-400 italic mb-4">
            Fait à {{ $company->city }}, le {{ now()->format('d/m/Y') }}
        </div>
        <div class="text-center w-5/12 border border-slate-200 p-4 rounded-xl bg-white shadow-sm">
            <p class="font-bold text-[10px] uppercase text-slate-600 mb-20">Pour l'Employeur ({{ $company->legal_name }})</p>
            <p class="text-[8px] text-slate-400 italic">Visa de la direction ou du gestionnaire de flotte</p>
        </div>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[8px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Contrat de détention Flotte Batistack - ID d'affectation #{{ $assignment->id }}</div>
        <div class="font-bold uppercase tracking-wider">Flotte Assignment Form v2.0</div>
    </footer>
@endsection
