@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">RAPPORT D'AUDIT FACTURE FOURNISSEUR</div>
        <div class="value">{{ $invoice->reference }}</div>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 5px 0;">
        <div style="font-size: 10px; margin-top: 10px;">
            <div><strong>Date :</strong> {{ $invoice->created_at?->format('d/m/Y') }}</div>
            <div><strong>Statut :</strong> {{ $invoice->status?->getLabel() ?? $invoice->status }}</div>
            @if($invoice->due_date)
                <div><strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .verdict-box {
            border-radius: 4px;
            padding: 12px;
            margin: 20px 0;
            font-size: 13px;
            font-weight: bold;
        }
        .verdict-valid {
            background-color: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        .verdict-invalid {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .dispute-item {
            padding: 8px 10px;
            border-left: 3px solid #fecaca;
            background: #fff7ed;
            margin-bottom: 6px;
            font-size: 10px;
        }
    </style>
@endsection

@section('content')
    <div class="client-section">
        <div class="client-info">
            <div class="section-title">FOURNISSEUR</div>
            <div class="section-content">
                <strong>{{ $invoice->supplier?->name }}</strong><br>
                @php $supplierAddress = $invoice->supplier?->getMainAddress(); @endphp
                @if($supplierAddress)
                    {{ $supplierAddress->street }}<br>
                    {{ $supplierAddress->zip_code }} {{ $supplierAddress->city }}
                @endif
                @if($invoice->supplier?->phone)
                    Tél: {{ $invoice->supplier->phone }}<br>
                @endif
                @if($invoice->supplier?->email)
                    Email: {{ $invoice->supplier->email }}
                @endif
            </div>
        </div>

        <div class="project-info">
            <div class="section-title">COMMANDE LIÉE</div>
            <div class="section-content">
                @if($invoice->order)
                    <strong>{{ $invoice->order->reference }}</strong><br>
                    Date : {{ $invoice->order->ordered_at?->format('d/m/Y') ?? $invoice->order->created_at?->format('d/m/Y') }}
                @else
                    <em>Aucune commande associée</em>
                @endif
            </div>
        </div>
    </div>

    @php
        $isValid = $audit['is_valid'] ?? true;
        $disputes = $audit['disputes'] ?? [];
    @endphp

    <div class="verdict-box {{ $isValid ? 'verdict-valid' : 'verdict-invalid' }}">
        @if($isValid)
            ✔ FACTURE CONFORME — Aucun écart détecté.
        @else
            ✘ ANOMALIES DÉTECTÉES — {{ count($disputes) }} point(s) à corriger.
        @endif
    </div>

    @if(!$isValid && count($disputes) > 0)
        <div class="section-title">DÉTAIL DES ANOMALIES</div>
        <div style="margin: 10px 0;">
            @forelse($disputes as $dispute)
                <div class="dispute-item">{{ $dispute }}</div>
            @empty
                <em>Aucun détail.</em>
            @endforelse
        </div>
    @endif

    <div class="section-title" style="margin-top: 20px;">LIGNES DE LA FACTURE</div>
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 55%;">DESCRIPTION</th>
            <th class="qty">QTÉ</th>
            <th class="price">PU HT</th>
            <th class="total">TOTAL HT</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoice->items as $item)
            <tr>
                <td><strong>{{ $item->item?->name ?? $item->name }}</strong></td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($item->price_unit, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item->quantity * $item->price_unit, 2, ',', ' ') }} €</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center"><em>Facture sans ligne détaillée.</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="end-section" style="page-break-inside: avoid;">
        <div class="summary-section">
            <div style="clear: both;">
                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td class="label">TOTAL HT</td>
                            <td class="value">{{ number_format($invoice->amount_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">TOTAL TTC</td>
                            <td class="value">{{ number_format($invoice->amount_ttc, 2, ',', ' ') }} €</td>
                        </tr>
                        @if(property_exists($invoice, 'amount_remaining') && $invoice->amount_remaining !== null)
                            <tr class="total-row">
                                <td class="label">RESTANT DÛ TTC</td>
                                <td class="value">{{ number_format($invoice->amount_remaining, 2, ',', ' ') }} €</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
@endsection