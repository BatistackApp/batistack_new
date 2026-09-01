@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Lettre de Licenciement</h1>
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
            <strong>Objet : Notification de licenciement</strong>
        </p>
        <p>Madame, Monsieur,</p>
        <p>
            Vous avez été engagé(e) le {{ $contract->start_date->format('d/m/Y') }} en qualité de {{ $contract->job_title }},
            moyennant un taux horaire de {{ number_format($contract->hourly_rate, 2, ',', ' ') }} €.
        </p>
        <p>
            Suite aux faits qui ont motivé cette décision, nous avons le regret de vous informer que nous avons décidé de
            procéder à votre licenciement pour motif {{ $contract->termination_reason ? strtolower($contract->termination_reason) : 'professionnel' }}.
        </p>
        <p>
            Conformément aux dispositions légales en vigueur et à la convention collective applicable,
            nous vous accordons un préavis d'une durée de <strong>{{ $noticeDays ?? '-' }} jour(s)</strong>,
            soit du {{ now()->format('d/m/Y') }} au <strong>{{ $contract->notice_end_date ? $contract->notice_end_date->format('d/m/Y') : '-' }}</strong>.
        </p>
        @if($contract->termination_amount)
        <p>
            Vous percevrez une indemnité de licenciement d'un montant de
            <strong>{{ number_format($contract->termination_amount, 2, ',', ' ') }} €</strong> brute,
            conformément aux dispositions légales.
        </p>
        @endif
        <p>
            À la fin de votre préavis, il vous sera remis : un certificat de travail, une attestation France Travail
            ainsi qu'un reçu pour solde de tout compte.
        </p>
        <p>
            Vous disposez d'un délai de 12 mois à compter de la notification de la présente lettre pour contester cette décision
            devant le Conseil de Prud'hommes.
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
