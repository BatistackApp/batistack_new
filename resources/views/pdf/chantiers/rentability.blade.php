@extends('pdf.layout')

@section('styles')
    <style>
        .progress-container { background-color: #edf2f7; border-radius: 9999px; height: 10px; width: 100%; position: relative; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 9999px; transition: width 0.3s ease; }
        .bg-success { background-color: #10b981; }
        .bg-warning { background-color: #f59e0b; }
        .bg-danger { background-color: #ef4444; }
    </style>
@endsection

@section('content')
    <div class="flex justify-between items-start mb-8 border-b-4 border-blue-batistack pb-4">
        <div>
            <h1 class="text-3xl font-bold text-blue-batistack uppercase">Bilan Analytique Chantier</h1>
            <p class="text-xl text-slate-600">{{ $chantier->name }} ({{ $chantier->reference }})</p>
        </div>
        <div class="text-right">
            <div @class([
                'px-4 py-2 rounded-lg font-bold text-white',
                'bg-blue-batistack' => $chantier->status->value === 'progress',
                'bg-green-600' => $chantier->status->value === 'finished',
            ])>
                Statut : {{ $chantier->status->getLabel() }}
            </div>
            <p class="text-[10px] mt-2 text-slate-500">Période du {{ $chantier->start_date?->format('d/m/Y') ?? 'Non démarré' }} au {{ $chantier->end_date?->format('d/m/Y') ?? 'En cours' }}</p>
        </div>
    </div>

    <!-- RÉSUMÉ KPI -->
    <div class="grid grid-cols-4 gap-4 mb-10">
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Avancement Technique</span><br>
            <span class="text-2xl font-bold text-blue-batistack">{{ number_format($metrics['progress'], 1) }} %</span>
            <div class="progress-container mt-2">
                <div class="progress-bar bg-blue-batistack" style="width: {{ $metrics['progress'] }}%"></div>
            </div>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Heures Consommées</span><br>
            <span @class(['text-2xl font-bold', 'text-red-600' => $metrics['hours']['percent'] > 100, 'text-green-600' => $metrics['hours']['percent'] <= 100])>
                {{ $metrics['hours']['real'] }} h
            </span>
            <p class="text-[8px] text-slate-500 mt-1">Budget : {{ $metrics['hours']['budget'] }} h</p>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Coût MO Réel</span><br>
            <span class="text-2xl font-bold text-slate-800">{{ number_format($metrics['financials']['labor_cost_real'], 2) }} €</span>
        </div>
        <div class="bg-gray-header p-4 border rounded-xl shadow-sm text-center">
            <span class="text-[9px] uppercase font-bold text-slate-500">Marge Brute Estimée</span><br>
            @php $margin = $metrics['financials']['total_budget_ht'] - $metrics['financials']['total_cost_real']; @endphp
            <span @class(['text-2xl font-bold', 'text-green-600' => $margin > 0, 'text-red-600' => $margin <= 0])>
                {{ number_format($margin, 2) }} €
            </span>
        </div>
    </div>

    <!-- TABLEAU DÉTAILLÉ -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <section>
            <h2 class="text-[10px] font-bold bg-slate-800 text-white p-2 mb-2 uppercase">Ventilation des Coûts Réels</h2>
            <table class="text-[10px]">
                <thead>
                <tr>
                    <th class="text-left">Catégorie</th>
                    <th class="text-right">Montant HT</th>
                    <th class="text-right">% Budget</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="py-2 font-bold">Main d'œuvre (Heures validées)</td>
                    <td class="text-right">{{ number_format($metrics['financials']['labor_cost_real'], 2) }} €</td>
                    <td class="text-right italic">Calculé</td>
                </tr>
                <tr>
                    <td class="py-2 font-bold">Matériaux (Bons de sortie)</td>
                    <td class="text-right">{{ number_format($metrics['financials']['material_cost_real'] ?? 0, 2) }} €</td>
                    <td class="text-right text-slate-500">{{ $metrics['financials']['material_budget'] > 0 ? round(($metrics['financials']['material_cost_real'] / $metrics['financials']['material_budget']) * 100) : 0 }} %</td>
                </tr>
                <tr>
                    <td class="py-2 font-bold">Sous-traitance</td>
                    <td class="text-right">{{ number_format($metrics['financials']['subcontracting_cost_real'] ?? 0, 2) }} €</td>
                    <td class="text-right text-slate-500">-</td>
                </tr>
                </tbody>
                <tfoot class="bg-slate-50 font-bold">
                <tr>
                    <td class="py-2 uppercase">Total Coût de Revient</td>
                    <td class="text-right">{{ number_format($metrics['financials']['total_cost_real'], 2) }} €</td>
                    <td class="text-right">{{ $metrics['financials']['budget_ht'] > 0 ? round(($metrics['financials']['total_cost_real'] / $metrics['financials']['budget_ht']) * 100) : 0 }} %</td>
                </tr>
                </tfoot>
            </table>
        </section>

        <section>
            <h2 class="text-[10px] font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">Analyse de Performance</h2>
            <div class="p-4 border border-slate-200 rounded-lg space-y-4">
                <div>
                    <p class="text-[10px] text-slate-500 uppercase">Indicateur de Rentabilité :</p>
                    @if($margin > ($metrics['financials']['total_budget_ht'] * 0.2))
                        <p class="text-green-600 font-bold">EXCELLENT (Marge > 20%)</p>
                    @elseif($margin > 0)
                        <p class="text-orange-500 font-bold">CONFORME (Marge positive)</p>
                    @else
                        <p class="text-red-600 font-bold">ALERTE (Chantier en perte)</p>
                    @endif
                </div>
                <div class="pt-4 border-t">
                    <p class="text-[9px] text-justify leading-snug">Ce document synthétise l'ensemble des données financières capturées à ce jour. Le coût de la main d'œuvre est basé sur les pointages validés par le conducteur de travaux et les taux horaires chargés des salariés.</p>
                </div>
            </div>
        </section>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[7px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Bilan analytique confidentiel - Batistack ERP</div>
        <div class="uppercase tracking-tighter">Généré le {{ $generated_at }}</div>
    </footer>
@endsection
