@extends('pdf.layout')

@section('content')
    <div class="client-section mt-8">
        <div class="client-info">
            <h2 class="section-title">CLIENT</h2>
            <div class="section-content font-bold">
                {{ $client->name }}<br>
            </div>
            <div class="section-content mt-1">
                {{ $client->address }}<br>
                {{ $client->zip_code }} {{ $client->city }}
            </div>
            @if($client->phone)
                <div class="section-content mt-1">Tél : {{ $client->phone }}</div>
            @endif
        </div>
        
        <div class="project-info">
            <h2 class="section-title">BON D'INTERVENTION</h2>
            <div class="section-content">
                <strong>Référence :</strong> {{ $intervention->reference }}<br>
                <strong>Date :</strong> {{ \Carbon\Carbon::parse($intervention->scheduled_at ?? $intervention->created_at)->format('d/m/Y') }}<br>
                <strong>Type :</strong> {{ $intervention->type->getLabel() }}<br>
                <strong>Statut :</strong> {{ $intervention->status->getLabel() }}
            </div>
            @if($chantier)
                <div class="section-content mt-2">
                    <strong>Chantier :</strong> {{ $chantier->reference }} - {{ $chantier->name }}<br>
                    {{ $chantier->address }}, {{ $chantier->zip_code }} {{ $chantier->city }}
                </div>
            @endif
        </div>
    </div>

    @if($intervention->description)
        <div class="mt-6 mb-6">
            <h2 class="section-title">DESCRIPTION DE LA PANNE / TRAVAUX</h2>
            <div class="section-content p-4 bg-gray-50 border rounded-md">
                {!! strip_tags($intervention->description, '<br><p><ul><li><strong><em>') !!}
            </div>
        </div>
    @endif

    <div class="mt-8">
        <h2 class="section-title">MAIN D'ŒUVRE (TECHNICIENS)</h2>
        @if($workers->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="w-1/2">Intervenant</th>
                        <th class="w-1/2 text-right">Heures effectuées</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workers as $worker)
                        <tr>
                            <td>{{ $worker->employee->first_name }} {{ $worker->employee->last_name }}</td>
                            <td class="text-right">{{ $worker->hours_worked }} h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-sm italic text-gray-500">Aucun technicien affecté.</div>
        @endif
    </div>

    <div class="mt-8">
        <h2 class="section-title">PIÈCES ET MATÉRIEL FOURNIS</h2>
        @if($materials->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="w-3/4">Désignation de l'article</th>
                        <th class="w-1/4 text-right">Quantité</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materials as $material)
                        <tr>
                            <td>{{ $material->item->name }}</td>
                            <td class="text-right">{{ $material->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-sm italic text-gray-500">Aucun matériel utilisé.</div>
        @endif
    </div>

    <div class="conditions mt-12">
        <h3 class="conditions-title">SIGNATURE & ACCEPTATION</h3>
        <p class="conditions-content">
            L'intervention détaillée ci-dessus a été réalisée selon les conditions prévues. 
            La signature de ce document par le client vaut pour recette et acceptation sans réserve des travaux effectués et du matériel fourni. 
            @if($intervention->type->value === 'regie')
            Ce bon d'intervention servira de base à la facturation des heures et des pièces en régie.
            @endif
        </p>
    </div>
@endsection
