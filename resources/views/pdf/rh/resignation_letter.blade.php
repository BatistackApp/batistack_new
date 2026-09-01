@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Accusé de Réception de Démission</h1>
    </div>

    <div class="mb-10 text-right">
        <p>A {{ $company->city }}, le {{ $contract->terminated_at ? $contract->terminated_at->format('d/m/Y') : now()->format('d/m/Y') }}</p>
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
            <strong>Objet : Accusé de réception de votre démission</strong>
        </p>
        <p>Madame, Monsieur,</p>
        <p>
            Par la présente, nous accusons bonne réception de votre démission en date du {{ $contract->terminated_at ? $contract->terminated_at->format('d/m/Y') : now()->format('d/m/Y') }},
            pour le poste de {{ $contract->job_title }} que vous occupez au sein de notre entreprise depuis le {{ $contract->start_date->format('d/m/Y') }}.
        </p>
        <p>
            Conformément aux dispositions de votre contrat de travail et de la convention collective applicable,
            nous prenons acte de votre volonté de quitter l'entreprise.
        </p>
        @if($contract->notice_end_date)
        <p>
            Le préavis que vous devez effectuer, conformément à vos engagements contractuels, courra jusqu'au
            <strong>{{ $contract->notice_end_date->format('d/m/Y') }}</strong>.
        </p>
        @endif
        <p>
            À la date effective de fin de votre contrat, il vous sera remis : votre certificat de travail,
            l'attestation France Travail ainsi que votre reçu pour solde de tout compte.
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
