@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Reçu pour Solde de Tout Compte</h1>
    </div>

    <div class="mb-10 text-right">
        <p>A {{ $company->city }}, le {{ $contract->notice_end_date ? $contract->notice_end_date->format('d/m/Y') : now()->format('d/m/Y') }}</p>
    </div>

    <div class="mb-10 leading-relaxed">
        <p>
            <strong>Entre les soussignés :</strong><br><br>
            <strong>{{ $company->legal_name }}</strong>, {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}<br>
            ci-après désigné « l'Employeur »<br><br>
            <strong>{{ $employee->full_name }}</strong>, {{ $employee->full_address }}<br>
            ci-après désigné « le Salarié »
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <p>
            <strong>Objet : Solde de tout compte suite à la rupture du contrat de travail</strong>
        </p>
        <p>
            Le contrat de travail liant les parties a pris fin le {{ $contract->notice_end_date ? $contract->notice_end_date->format('d/m/Y') : now()->format('d/m/Y') }},
            pour motif : {{ $contract->termination_type ? $contract->termination_type->getLabel() : 'Rupture de contrat' }}.
        </p>

        <p>
            Le Salarié reconnaît avoir reçu de l'Employeur les sommes suivantes à titre de solde de tout compte,
            libérant ce dernier de toute obligation à son égard :
        </p>

        <table class="items-table mt-6">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th class="text-right">Montant (€)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Salaire jusqu'au {{ $contract->notice_end_date ? $contract->notice_end_date->format('d/m/Y') : now()->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($salary_amount ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>Indemnité de congés payés</td>
                    <td class="text-right">{{ number_format($paid_leave_amount ?? 0, 2, ',', ' ') }}</td>
                </tr>
                @if($contract->termination_amount)
                <tr>
                    <td>Indemnité de {{ $contract->termination_type ? strtolower($contract->termination_type->getLabel()) : 'rupture' }}</td>
                    <td class="text-right">{{ number_format($contract->termination_amount, 2, ',', ' ') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Indemnité compensatrice de préavis</td>
                    <td class="text-right">{{ number_format($notice_compensation ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>Frais de toute nature remboursés</td>
                    <td class="text-right">{{ number_format($expenses ?? 0, 2, ',', ' ') }}</td>
                </tr>
                @if($primes ?? false)
                <tr>
                    <td>Primes</td>
                    <td class="text-right">{{ number_format($primes, 2, ',', ' ') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ number_format($total ?? 0, 2, ',', ' ') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <p class="mt-6">
            Le Salarié déclare qu'il ne reste plus rien à payer entre les parties et que la présente quittance
            est donnée pour solder tout ce qui pourrait lui être dû à quelque titre que ce soit, sans exception ni réserve.
        </p>

        <p>
            Le Salarié se réserve toutefois le droit de contester les sommes versées s'il estime que celles-ci sont
            insuffisantes au regard de ses droits légaux et conventionnels.
        </p>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">L'Employeur</p>
            <p class="underline">Signature</p>
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Le Salarié</p>
            <p class="underline">Signature</p>
        </div>
    </div>
@endsection
