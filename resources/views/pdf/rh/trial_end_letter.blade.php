@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Lettre de Rupture de Période d'Essai</h1>
    </div>

    <div class="mb-10 text-right">
        <p>A {{ $company->city }}, le {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="mb-10 leading-relaxed">
        <p>
            <strong>Expéditeur :</strong> {{ $company->legal_name }}<br>
            {{ $company->address }}<br>
            {{ $company->zip_code }} {{ $company->city }}
        </p>
        <br>
        <p>
            <strong>Destinataire :</strong> M/Mme {{ $employee->full_name }}<br>
            {{ $employee->full_address }}
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <p>
            <strong>Objet : Rupture de votre période d'essai</strong>
        </p>
        <p>Madame, Monsieur,</p>
        <p>
            Vous avez été embauché(e) au sein de notre entreprise le {{ $contract->start_date->format('d/m/Y') }} en qualité de {{ $contract->job_title }}.
            Votre contrat de travail prévoyait une période d'essai de deux mois.
        </p>
        <p>
            Par la présente, nous vous informons que nous avons décidé de mettre fin à cette période d'essai, conformément aux dispositions prévues par votre contrat de travail et la convention collective applicable.
        </p>
        <p>
            En application du délai de prévenance légal en vigueur, votre contrat de travail prendra définitivement fin le <strong>{{ now()->addDays(2)->format('d/m/Y') }}</strong> (à titre indicatif, sous réserve du calcul exact du délai de prévenance).
        </p>
        <p>
            À la date de rupture de votre contrat, nous vous remettrons votre certificat de travail, votre reçu pour solde de tout compte, ainsi que votre attestation France Travail.
        </p>
        <p>
            Nous vous prions d'agréer, Madame, Monsieur, l'expression de nos salutations distinguées.
        </p>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">La Direction</p>
            <p class="underline">Signature</p>
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Remis en main propre le {{ now()->format('d/m/Y') }}</p>
            <p class="underline">Signature du Salarié</p>
        </div>
    </div>
@endsection
