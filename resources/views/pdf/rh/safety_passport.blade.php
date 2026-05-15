@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-8 border-b-2 border-blue-batistack pb-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-batistack uppercase">Passeport Sécurité Collaborateur</h1>
            <p class="text-slate-500 font-bold">Identité : {{ $employee->full_name }} (Matricule : {{ $employee->registration_number }})</p>
        </div>
        <div class="bg-gray-header p-2 border rounded text-center">
            <span class="text-[10px] text-slate-500 uppercase">Validité Document</span><br>
            <span class="font-bold text-green-600">Certifié Batistack</span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-10 mb-10">
        <section>
            <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Suivi Médical (Dernière VIP/SIR)</h2>
            @php $lastVisit = $employee->medicalVisits()->latest('visit_date')->first(); @endphp
            @if($lastVisit)
                <div class="space-y-2">
                    <p><strong>Type :</strong> {{ $lastVisit->type->getLabel() }}</p>
                    <p><strong>Date de visite :</strong> {{ $lastVisit->visit_date->format('d/m/Y') }}</p>
                    <p><strong>Aptitude :</strong>
                        <span @class(['font-bold', 'text-green-600' => $lastVisit->aptitude->value === 'fit', 'text-orange-600' => $lastVisit->aptitude->value === 'fit_restricted'])>
                        {{ $lastVisit->aptitude->getLabel() }}
                    </span>
                    </p>
                    <p><strong>Échéance :</strong> {{ $lastVisit->next_due_date->format('d/m/Y') }}</p>
                </div>
            @else
                <p class="text-red-500 italic">Aucune visite médicale enregistrée.</p>
            @endif
        </section>

        <section>
            <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Habilitations & CACES</h2>
            <table class="text-[10px]">
                <thead>
                <tr>
                    <th class="text-left">Libellé</th>
                    <th class="text-right">Échéance</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employee->qualifications as $qualif)
                    <tr>
                        <td class="py-1">{{ $qualif->label }}</td>
                        <td @class(['text-right', 'text-red-600 font-bold' => $qualif->expires_at->isPast()])>
                            {{ $qualif->expires_at->format('d/m/Y') }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-xs italic">
        <p>Ce document certifie que le collaborateur dispose des aptitudes médicales et des formations de sécurité nécessaires pour intervenir sur chantier à la date du {{ $generated_at }}.</p>
    </div>
@endsection
