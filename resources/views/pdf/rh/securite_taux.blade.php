@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Indicateurs de Sécurité</h1>
        <p class="text-slate-500 italic">Accidents du travail (AT) - Taux de Fréquence et Taux de Gravité</p>
        <p class="text-xs text-slate-400">Généré le {{ $generated_at ?? now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="avoid-break mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Période de référence</h2>
        <table class="text-[11px] w-full">
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="p-2 w-1/3 text-slate-500">Début de période</td>
                    <td class="p-2 font-semibold">{{ $rates['from']->format('d/m/Y') }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 text-slate-500">Fin de période</td>
                    <td class="p-2 font-semibold">{{ $rates['to']->format('d/m/Y') }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 text-slate-500">Heures travaillées</td>
                    <td class="p-2 font-semibold">{{ number_format($rates['hoursWorked'], 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="avoid-break mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Résultats</h2>
        <table class="text-[11px] w-full">
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th class="text-center">Valeur</th>
                    <th>Formule</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="p-2 font-semibold">Taux de Fréquence (TF)</td>
                    <td class="p-2 text-center font-bold">{{ number_format($rates['tf'], 2, ',', ' ') }}</td>
                    <td class="p-2 text-slate-500">{{ $rates['accidentCount'] }} × 1 000 000 ÷ {{ number_format($rates['hoursWorked'], 0, ',', ' ') }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 font-semibold">Taux de Gravité (TG)</td>
                    <td class="p-2 text-center font-bold">{{ number_format($rates['tg'], 2, ',', ' ') }}</td>
                    <td class="p-2 text-slate-500">{{ $rates['daysLost'] }} × 1 000 ÷ {{ number_format($rates['hoursWorked'], 0, ',', ' ') }}</td>
                </tr>
                <tr class="border-b border-slate-200 bg-gray-header">
                    <td class="p-2 font-semibold">Accidents du travail</td>
                    <td class="p-2 text-center font-bold">{{ $rates['accidentCount'] }}</td>
                    <td class="p-2 text-slate-500">Sur la période de 12 mois glissants</td>
                </tr>
                <tr class="border-b border-slate-200 bg-gray-header">
                    <td class="p-2 font-semibold">Journées perdues</td>
                    <td class="p-2 text-center font-bold">{{ $rates['daysLost'] }}</td>
                    <td class="p-2 text-slate-500">Jours calendaires d'arrêt (bornes incluses)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="avoid-break mb-10 text-[10px] leading-relaxed">
        <p class="font-bold mb-1">Rappel des formules réglementaires :</p>
        <p class="text-slate-600">
            TF = (Nombre d'accidents du travail avec arrêt × 1 000 000) ÷ Nombre d'heures travaillées.
        </p>
        <p class="text-slate-600">
            TG = (Nombre de journées perdues × 1 000) ÷ Nombre d'heures travaillées.
        </p>
        <p class="text-slate-500 mt-2">
            Indicateurs calculés en temps réel sur les 12 mois glissants à partir des feuilles de temps approuvées
            et des absences de type « Accident du travail ».
        </p>
    </div>

    <div class="avoid-break mt-16 flex justify-between items-end">
        <div class="text-center w-1/2">
            <p class="font-bold mb-20 text-[10px] uppercase underline">L'Entreprise ({{ $company->legal_name ?? 'Batistack' }})</p>
        </div>
        <div class="text-center w-1/2 text-[8px] text-slate-400 italic">
            Fait à {{ $company->city ?? '________________' }}, le {{ $generated_at ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>
@endsection