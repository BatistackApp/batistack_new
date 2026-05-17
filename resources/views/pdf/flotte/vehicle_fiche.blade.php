@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-8 border-b-4 border-blue-batistack pb-4">
        <div>
            <h1 class="text-3xl font-bold text-blue-batistack uppercase">Fiche Technique & Financière</h1>
            <p class="text-xl text-slate-600">{{ $vehicle->brand }} {{ $vehicle->model }} (Réf: {{ $vehicle->reference }})</p>
        </div>
        <div class="text-right">
            <span class="font-mono text-xs bg-slate-800 text-white px-3 py-1.5 rounded-lg font-bold">{{ $vehicle->license_plate }}</span>
            <p class="text-[9px] mt-2 text-slate-500">Généré le {{ $generated_at }}</p>
        </div>
    </div>

    <!-- SYNTHÈSE DES COÛTS (TCO - TOTAL COST OF OWNERSHIP) -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Coût Total (TCO)</span><br>
            <span class="text-xl font-bold text-blue-batistack">{{ number_format($tco, 2, ',', ' ') }} €</span>
            <p class="text-[8px] text-slate-500 mt-1">Investissement + Maintenance</p>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Prix d'Achat</span><br>
            <span class="text-xl font-bold text-slate-800">{{ number_format($vehicle->purchase_price, 2, ',', ' ') }} €</span>
            <p class="text-[8px] text-slate-500 mt-1">Acquis le {{ $vehicle->purchase_date?->format('d/m/Y') ?? 'N/A' }}</p>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Coût Maintenance</span><br>
            @php $maintenancesSum = $vehicle->maintenances()->sum('cost_ht'); @endphp
            <span class="text-xl font-bold text-orange-600">{{ number_format($maintenancesSum, 2, ',', ' ') }} €</span>
            <p class="text-[8px] text-slate-500 mt-1">Cumulé sur {{ $vehicle->maintenances()->count() }} interventions</p>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Consommation & Kms</span><br>
            <span class="text-xl font-bold text-emerald-600">{{ number_format($vehicle->odometer, 1, ',', ' ') }} km</span>
            <p class="text-[8px] text-slate-500 mt-1">Taux : {{ number_format($vehicle->km_rate, 4, ',', ' ') }} €/km</p>
        </div>
    </div>

    <!-- CARACTÉRISTIQUES TECHNIQUES -->
    <div class="mb-8">
        <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-3 uppercase">Caractéristiques de l'Actif</h2>
        <div class="grid grid-cols-2 gap-8 text-[10px]">
            <table class="w-full">
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Marque :</td>
                    <td class="text-right font-bold">{{ $vehicle->brand }}</td>
                </tr>
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Modèle :</td>
                    <td class="text-right">{{ $vehicle->model }}</td>
                </tr>
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Énergie / Carburant :</td>
                    <td class="text-right">{{ $vehicle->fuel_type }}</td>
                </tr>
            </table>
            <table class="w-full">
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Catégorie d'actif :</td>
                    <td class="text-right">{{ $vehicle->type->getLabel() }}</td>
                </tr>
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Statut Courant :</td>
                    <td class="text-right font-bold text-blue-batistack">{{ $vehicle->status->getLabel() }}</td>
                </tr>
                <tr class="h-8 border-b">
                    <td class="text-slate-500 font-bold uppercase">Tarif Interne Journalier :</td>
                    <td class="text-right font-mono">{{ number_format($vehicle->daily_rate, 2, ',', ' ') }} € HT</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- HISTORIQUE RÉCENT DES MAINTENANCES & RÉPARATIONS -->
    <div class="mb-8">
        <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-3 uppercase">Dépenses de Maintenance Récentes</h2>
        <table class="text-[9px]">
            <thead>
            <tr class="bg-slate-100 text-slate-700">
                <th class="py-2 px-3 text-left">Date</th>
                <th class="text-left">Type de Travaux</th>
                <th class="text-left">Partenaire / Garage</th>
                <th class="text-right">Kilométrage</th>
                <th class="text-right">Montant HT</th>
            </tr>
            </thead>
            <tbody>
            @forelse($vehicle->maintenances()->orderBy('performed_at', 'desc')->take(5)->get() as $m)
                <tr>
                    <td class="py-2 px-3 font-mono">{{ $m->performed_at->format('d/m/Y') }}</td>
                    <td class="font-bold">{{ $m->type }}</td>
                    <td>{{ $m->supplier->name }}</td>
                    <td class="text-right font-mono">{{ number_format($m->odometer_at_maintenance, 0, ',', ' ') }} km</td>
                    <td class="text-right font-mono font-bold">{{ number_format($m->cost_ht, 2, ',', ' ') }} €</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-4 italic text-slate-400 text-center">Aucun événement de maintenance mécanique enregistré.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- CONTRATS D'ASSURANCE & AMENDES ASSOCIÉES -->
    <div class="grid grid-cols-2 gap-8">
        <!-- COUVERTURE & ASSURANCE -->
        <section>
            <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-3 uppercase">Contrats & Assurances actifs</h2>
            <table class="text-[9px]">
                <thead>
                <tr class="bg-slate-50 text-slate-600">
                    <th class="text-left py-1.5">Contrat / Police</th>
                    <th class="text-left">Partenaire</th>
                    <th class="text-right">Échéance</th>
                </tr>
                </thead>
                <tbody>
                @forelse($vehicle->contracts as $c)
                    <tr>
                        <td class="py-2"><strong>{{ $c->type }}</strong><br><span class="text-[8px] font-mono text-slate-500">{{ $c->policy_number }}</span></td>
                        <td>{{ $c->supplier->name }}</td>
                        <td class="text-right font-mono">{{ $c->end_date ? $c->end_date->format('d/m/Y') : 'Contrat Permanent' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-3 italic text-slate-400 text-center">Aucune police d'assurance active enregistrée.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <!-- CONTRAVENTIONS SIGNALÉES -->
        <section>
            <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-3 uppercase">Contraventions & PV rattachés</h2>
            <table class="text-[9px]">
                <thead>
                <tr class="bg-slate-50 text-slate-600">
                    <th class="text-left py-1.5">Référence</th>
                    <th class="text-left">Conducteur désigné</th>
                    <th class="text-right">Infraction le</th>
                    <th class="text-right">Amende</th>
                </tr>
                </thead>
                <tbody>
                @forelse($vehicle->fines as $f)
                    <tr>
                        <td class="py-2 font-mono font-bold">{{ $f->reference }}</td>
                        <td>{{ $f->employee?->full_name ?? 'Inconnu' }}</td>
                        <td class="text-right font-mono">{{ $f->infraction_at->format('d/m/Y') }}</td>
                        <td class="text-right text-red-600 font-bold font-mono">{{ number_format($f->amount, 2, ',', ' ') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-3 italic text-green-600 text-center">Zéro contravention enregistrée pour ce véhicule.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[7px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Bilan d'exploitation analytique - Batistack ERP</div>
        <div class="uppercase tracking-tighter">Référence d'Actif : {{ $vehicle->reference }}</div>
    </footer>
@endsection
