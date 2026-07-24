@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Contrat de Travail</h1>
        <p class="text-slate-500 italic">Type de contrat : {{ $contract->type->getDescription() }}</p>
    </div>

    <div class="mb-10 leading-relaxed">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4">ENTRE LES SOUSSIGNÉS :</h2>
        <p class="mb-4">
            <strong>L'Employeur :</strong> {{ $company->legal_name }}, situé au {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}.<br>
            Représenté par son dirigeant en exercice.
        </p>
        <p>
            <strong>Le Salarié :</strong> M/Mme {{ $employee->full_name }}, demeurant au {{ $employee->full_address }}, né(e) le {{ $employee->birth_date->format('d/m/Y') }}.<br>
            Numéro de Sécurité Sociale : <span class="font-mono">{{ $employee->social_security_number }}</span>
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 1 : Engagement et Fonctions</h3>
            <p>Le salarié est engagé à compter du <strong>{{ $contract->start_date->format('d/m/Y') }}</strong> en qualité de <strong>{{ $contract->job_title }}</strong>.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 2 : Rémunération</h3>
            <p>En contrepartie de son travail, le salarié percevra une rémunération horaire brute de <strong>{{ number_format($contract->hourly_rate, 2) }} €</strong>, pour une durée hebdomadaire de <strong>{{ $contract->weekly_hours }} heures</strong>.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 3 : Lieu de Travail</h3>
            <p>Le lieu de travail est fixé au siège de l'entreprise. Toutefois, compte tenu de la nature de l'activité du bâtiment, le salarié sera amené à se déplacer sur les différents chantiers de l'entreprise.</p>
        </section>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Fait à {{ $company->city }}, le {{ now()->format('d/m/Y') }}</p>
            <p class="underline">Signature de l'Employeur</p>
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Mention "Lu et approuvé"</p>
            @if(isset($signature) && $signature->signature_data)
                <div style="font-size: 10px; color: #555; text-align: center; margin-bottom: 5px;">
                    Signé par {{ $employee->full_name }}<br>
                    le {{ $signature->signed_at ? $signature->signed_at->format('d/m/Y à H:i:s') : now()->format('d/m/Y à H:i:s') }}<br>
                    Réf : {{ $signature->token }}
                </div>
                <img src="{{ $signature->signature_data }}" style="max-height: 80px; margin: 0 auto;" alt="Signature">
            @else
                <p class="underline">Signature du Salarié</p>
            @endif
        </div>
    </div>
@endsection
