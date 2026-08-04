@extends('pdf.layout')

@php
    $totalInvoices = $invoices->sum(fn ($invoice) => (float) $invoice->total_ttc);
    $totalPayments = $payments->sum(fn ($payment) => (float) $payment->amount);
    $balance = $totalInvoices - $totalPayments;
    $statusLabel = $status ? (\App\Enums\Commerce\InvoiceStatus::tryFrom($status)?->getLabel() ?? $status) : 'Tous';
@endphp

@section('header_right')
    <div class="invoice-info">
        <div class="label">RELEVE CLIENT</div>
        <div class="value">{{ $client->name }}</div>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 5px 0;">
        <div style="font-size: 10px; margin-top: 10px;">
            <div><strong>Periode :</strong> {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>
            <div><strong>Statut :</strong> {{ $statusLabel }}</div>
            <div><strong>Genere le :</strong> {{ $generated_at }}</div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .statement-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .summary-box {
            border: 1px solid #dbeafe;
            background: #eff6ff;
            padding: 10px;
            border-radius: 4px;
        }

        .summary-label {
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #1e40af;
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }

        .section-heading {
            color: #1e40af;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 8px;
            text-transform: uppercase;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
@endsection

@section('content')
    <div class="info-section">
        <div class="client-info">
            <div class="section-title">CLIENT</div>
            <div class="section-content">
                <strong>{{ $client->name }}</strong><br>
                @if($client->legal_name)
                    {{ $client->legal_name }}<br>
                @endif
                @if($client->siret)
                    SIRET : {{ $client->siret }}<br>
                @endif
                @if($client->email)
                    Email : {{ $client->email }}
                @endif
            </div>
        </div>

        <div class="payment-info">
            <div class="section-title">FILTRES</div>
            <div class="section-content">
                <strong>Debut :</strong> {{ $startDate->format('d/m/Y') }}<br>
                <strong>Fin :</strong> {{ $endDate->format('d/m/Y') }}<br>
                <strong>Statut :</strong> {{ $statusLabel }}
            </div>
        </div>
    </div>

    <div class="statement-summary">
        <div class="summary-box">
            <div class="summary-label">Total facture</div>
            <div class="summary-value">{{ number_format($totalInvoices, 2, ',', ' ') }} EUR</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Paiements recus</div>
            <div class="summary-value">{{ number_format($totalPayments, 2, ',', ' ') }} EUR</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Solde indicatif</div>
            <div class="summary-value">{{ number_format($balance, 2, ',', ' ') }} EUR</div>
        </div>
    </div>

    <div class="section-heading">Factures</div>
    <table class="items-table">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Date</th>
            <th>Echeance</th>
            <th>Statut</th>
            <th class="text-right">Montant TTC</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td>{{ $invoice->reference }}</td>
                <td>{{ $invoice->created_at?->format('d/m/Y') }}</td>
                <td>{{ $invoice->due_date?->format('d/m/Y') }}</td>
                <td>{{ $invoice->status?->getLabel() }}</td>
                <td class="text-right">{{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} EUR</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Aucune facture sur cette periode.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-heading">Paiements</div>
    <table class="items-table">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Date</th>
            <th>Methode</th>
            <th>Statut</th>
            <th class="text-right">Montant</th>
        </tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->reference }}</td>
                <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
                <td>{{ $payment->method?->getLabel() ?? $payment->method?->value }}</td>
                <td>{{ $payment->status?->getLabel() ?? $payment->status?->value }}</td>
                <td class="text-right">{{ number_format((float) $payment->amount, 2, ',', ' ') }} EUR</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Aucun paiement sur cette periode.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>{{ $company->name }} - Genere le {{ $generated_at }}</div>
        <div>Ce releve est fourni a titre informatif et ne remplace pas les factures legales.</div>
    </div>
@endsection
