@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Convention de Rupture de Contrat à Durée Indéterminée</h1>
    </div>

    <div class="mb-10 leading-relaxed">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4">ENTRE LES SOUSSIGNÉS :</h2>
        <p class="mb-4">
            <strong>L'Employeur :</strong> {{ $company->legal_name }}, dont le siège social est situé
            au {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }},<br>
            représenté par {{ $company->legal_representative ?? 'Son représentant légal' }}.
        </p>
        <p>
            <strong>Le Salarié :</strong> M/Mme {{ $employee->full_name }}, demeurant au {{ $employee->full_address }}.
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <p>
            <strong>Article 1 – Objet de la convention</strong>
        </p>
        <p>
            La présente convention a pour objet de constater l'accord des parties pour mettre fin au contrat de travail
            à durée indéterminée lié au Salarié, en date du {{ $contract->start_date->format('d/m/Y') }}, pour occuper
            les fonctions de {{ $contract->job_title }}.
        </p>

        <p>
            <strong>Article 2 – Date de rupture</strong>
        </p>
        <p>
            La date de rupture du contrat de travail est fixée au <strong>{{ $contract->notice_end_date ? $contract->notice_end_date->format('d/m/Y') : '-' }}</strong>,
            prenant effet le jour de la signature de la présente convention par les deux parties.
        </p>

        <p>
            <strong>Article 3 – Indemnités</strong>
        </p>
        @if($contract->termination_amount)
        <p>
            L'Employeur versera au Salarié une indemnité conventionnelle de rupture d'un montant de
            <strong>{{ number_format($contract->termination_amount, 2, ',', ' ') }} €</strong> brute,
            en plus de l'indemnité légale de licenciement à laquelle le Salarié peut prétendre.
        </p>
        @else
        <p>
            L'Employeur versera au Salarié les indemnités prévues par la convention collective applicable et la législation en vigueur.
        </p>
        @endif

        <p>
            <strong>Article 4 – Congé de reclassement</strong>
        </p>
        <p>
            Conformément aux articles L.1234-18 et suivants du Code du travail, l'Employeur s'engage à faciliter le
            reclassement du Salarié pendant la durée du préavis.
        </p>

        <p>
            <strong>Article 5 – Documents de fin de contrat</strong>
        </p>
        <p>
            À la date de rupture du contrat, l'Employeur remettra au Salarié : le certificat de travail,
            l'attestation France Travail, le reçu pour solde de tout compte, ainsi que la convention de rupture.
        </p>

        <p>
            Fait en deux exemplaires originaux, à {{ $company->city }}, le {{ now()->format('d/m/Y') }}.
        </p>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/2">
            <p class="font-bold mb-10 text-xs uppercase">L'Employeur</p>
            <p class="underline">Signature et cachet</p>
        </div>
        <div class="text-center w-1/2">
            <p class="font-bold mb-10 text-xs uppercase">Le Salarié</p>
            <p class="underline">Lu et approuvé – Signature</p>
        </div>
    </div>
@endsection
