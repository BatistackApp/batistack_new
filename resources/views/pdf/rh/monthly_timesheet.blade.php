@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-6 border-b-2 border-blue-batistack pb-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-batistack uppercase">Relevé d'Activité Mensuel</h1>
            <p class="text-lg font-bold">Période : {{ $month }}/{{ $year }}</p>
        </div>
        <div class="text-right">
            <p class="font-bold">{{ $employee->full_name }}</p>
            <p class="text-xs text-slate-500 italic">Matricule : {{ $employee->registration_number }}</p>
        </div>
    </div>

    {{-- RÉSUMÉ ANALYTIQUE --}}
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="p-3 bg-gray-header border rounded text-center">
            <span class="text-[10px] uppercase text-slate-500">Heures Travail</span><br>
            <span class="text-xl font-bold text-blue-batistack">{{ $summary['total_hours'] }} h</span>
        </div>
        <div class="p-3 bg-gray-header border rounded text-center">
            <span class="text-[10px] uppercase text-slate-500">Heures Trajet</span><br>
            <span class="text-xl font-bold text-slate-700">{{ $summary['total_travel_hours'] ?? 0 }} h</span>
        </div>
        <div class="p-3 bg-gray-header border rounded text-center">
            <span class="text-[10px] uppercase text-slate-500">Paniers / GD</span><br>
            <span class="text-xl font-bold text-green-600">{{ $summary['gd_days'] }} j</span>
        </div>
        <div class="p-3 bg-gray-header border rounded text-center">
            <span class="text-[10px] uppercase text-slate-500">Heures Sup.</span><br>
            <span class="text-xl font-bold text-orange-600">{{ ($summary['overtime_25'] ?? 0) + ($summary['overtime_50'] ?? 0) }} h</span>
        </div>
    </div>

    {{-- DÉTAIL QUOTIDIEN --}}
    <table class="text-[10px] mb-8">
        <thead>
        <tr>
            <th class="text-left w-24">Date</th>
            <th class="text-left">Chantier / Imputation</th>
            <th class="text-center">Travail</th>
            <th class="text-center">Trajet</th>
            <th class="text-center">Type</th>
            <th class="text-center">GD</th>
        </tr>
        </thead>
        <tbody>
        @foreach($entries as $entry)
            <tr>
                <td class="py-1 font-bold">{{ $entry->date->format('d/m/Y') }}</td>
                <td class="text-slate-600">{{ $entry->jobSite?->name ?? 'Dépôt / Interne' }}</td>
                <td class="text-center font-bold">{{ number_format($entry->hours, 2) }} h</td>
                <td class="text-center text-slate-500">{{ number_format($entry->travel_hours, 2) }} h</td>
                <td class="text-center">{{ $entry->type->getLabel() }}</td>
                <td class="text-center">
                    @if($entry->is_grand_deplacement)
                        <span class="text-green-600 font-bold">96€</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- SECTION VALIDATION --}}
    <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg text-[9px] mb-12">
        <p><strong>Note :</strong> Ce relevé récapitule les heures approuvées par la conduite de travaux. En signant ce document, le salarié reconnaît l'exactitude des temps de travail et de trajet déclarés pour la période concernée.</p>
    </div>

    <div class="flex justify-between items-end">
        <div class="w-1/3 border-t-2 border-slate-200 pt-2 text-center">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-12">Signature Employeur</p>
        </div>
        <div class="w-1/3 text-center italic text-slate-400 text-[8px]">
            Document généré par Batistack ERP le {{ $generated_at }}
        </div>
        <div class="w-1/3 border-t-2 border-slate-200 pt-2 text-center">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-12">Signature Salarié</p>
        </div>
    </div>

@endsection
