@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Avenant de Rupture Anticipée de CDD</h1>
    </div>

    <div class="mb-10 leading-relaxed">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4">ENTRE LES SOUSSIGNÉS :</h2>
        <p class="mb-4">
            <strong>L'Employeur :</strong> {{ $company->legal_name }}, situé au {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}.
        </p>
        <p>
            <strong>Le Salarié :</strong> M/Mme {{ $employee->full_name }}, demeurant au {{ $employee->full_address }}.
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <p>
            <strong>IL A ÉTÉ CONVENU CE QUI SUIT :</strong>
        </p>
        <p>
            Le Salarié a été engagé par contrat de travail à durée déterminée en date du {{ $contract->start_date->format('d/m/Y') }} 
            pour occuper les fonctions de {{ $contract->job_title }}. Le terme initialement prévu pour ce contrat était le {{ $contract->end_date ? $contract->end_date->format('d/m/Y') : 'Non défini' }}.
        </p>
        <p>
            D'un commun accord, les parties ont décidé de mettre fin par anticipation au dit contrat de travail à durée déterminée, 
            conformément aux dispositions de l'article L. 1243-1 du Code du travail.
        </p>
        <p>
            Le présent contrat de travail prendra par conséquent fin le <strong>{{ $termination_date ? $termination_date->format('d/m/Y') : now()->format('d/m/Y') }}</strong> au soir.
        </p>
        <p>
            L'Employeur remettra au Salarié à cette date son certificat de travail, l'attestation France Travail et son reçu pour solde de tout compte.
        </p>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Fait à {{ $company->city }}, le {{ now()->format('d/m/Y') }}</p>
            <p class="underline">L'Employeur</p>
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Mention "Lu et approuvé"</p>
            <p class="underline">Le Salarié</p>
        </div>
    </div>
@endsection
