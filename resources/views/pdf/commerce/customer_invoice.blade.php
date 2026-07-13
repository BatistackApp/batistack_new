@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">FACTURE</div>
        <div class="value">{{ $invoice->reference }}</div>
        <span class="invoice-type {{ strtolower($invoice->type->value) }}">
                    {{ $invoice->type->getLabel() }}
                </span>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 5px 0;">
        <div style="font-size: 10px; margin-top: 10px;">
            <div><strong>Date :</strong> {{ $invoice->created_at->format('d/m/Y') }}</div>
            <div><strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>
            <div><strong>Numéro légalisé :</strong> {{ $invoice->reference }}</div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .invoice-info .value {
            font-size: 13px;
            font-weight: bold;
        }

        .invoice-type {
            display: inline-block;
            background-color: #fecaca;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
        }

        .invoice-type.simple { background-color: #dbeafe; color: #1e40af; }
        .invoice-type.acompte { background-color: #fed7aa; color: #9a3412; }
        .invoice-type.situation { background-color: #dcfce7; color: #166534; }

        /* Retenues */
        .retentions {
            clear: both;
            margin-top: 15px;
            padding: 10px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            font-size: 10px;
        }

        .retentions-title {
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 5px;
        }

        .retention-line {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .payment-terms {
            float: left;
            width: 55%;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 8px;
            border-radius: 4px;
            font-size: 10px;
            margin-top: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="info-section">
        <div class="client-info">
            <div class="section-title">CLIENT</div>
            <div class="section-content">
                <strong>{{ $invoice->client->name }}</strong><br>
                @if($invoice->client->addresses->first())
                    {{ $invoice->client->addresses->first()->street }}<br>
                    {{ $invoice->client->addresses->first()->zip_code }} {{ $invoice->client->addresses->first()->city }}<br>
                @endif
                @if($invoice->client->phone)
                    Tél: {{ $invoice->client->phone }}<br>
                @endif
                Email: {{ $invoice->client->email }}
            </div>
        </div>

        <div class="payment-info">
            <div class="section-title">CHANTIER</div>
            <div class="section-content">
                @if($invoice->chantier)
                    <strong>{{ $invoice->chantier->reference }}</strong><br>
                    {{ $invoice->chantier->address }}<br>
                    {{ $invoice->chantier->zip_code }} {{ $invoice->chantier->city }}
                @else
                    <em>Aucun chantier associé</em>
                @endif
            </div>

            <div class="section-title" style="margin-top: 15px;">CONDITION DE PAIEMENT</div>
            <div class="section-content">
                <strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}<br>
                <strong>Conditions :</strong> 30 jours net dès facture
            </div>
        </div>
    </div>

    <!-- Articles -->
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 45%;">DESCRIPTION</th>
            <th style="width: 10%; text-align: center;">QTÉ</th>
            <th style="width: 15%; text-align: right;">PRIX UNIT. HT</th>
            <th style="width: 15%; text-align: right;">TOTAL HT</th>
            <th style="width: 10%; text-align: center;">TVA</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoice->items as $item)
            <tr>
                <td><strong>{{ $item->name }}</strong></td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2, ',', ' ') }} €</td>
                <td class="text-center">{{ $item->vatRate->rate ?? '20' }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center"><em>Aucun article</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- Retenues et ajustements -->
    @if($invoice->retenue_guarantie_amount || $invoice->prorata_amount)
        <div class="retentions">
            <div class="retentions-title">RETENUES ET AJUSTEMENTS</div>
            @if($invoice->retenue_guarantie_amount)
                <div class="retention-line">
                    <span>Retenue de garantie (5%) :</span>
                    <span>- {{ number_format($invoice->retenue_guarantie_amount, 2, ',', ' ') }} €</span>
                </div>
            @endif
            @if($invoice->prorata_amount)
                <div class="retention-line">
                    <span>Compte prorata :</span>
                    <span>- {{ number_format($invoice->prorata_amount, 2, ',', ' ') }} €</span>
                </div>
            @endif
        </div>
    @endif

    <!-- Totaux -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">TOTAL HT :</td>
                <td class="value">{{ number_format($invoice->total_ht, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="label">TVA (20%) :</td>
                <td class="value">{{ number_format($invoice->total_ttc - $invoice->total_ht, 2, ',', ' ') }} €</td>
            </tr>
            @if($invoice->retenue_guarantie_amount || $invoice->prorata_amount)
                <tr>
                    <td class="label">Retenues :</td>
                    <td class="value">- {{ number_format(($invoice->retenue_guarantie_amount ?? 0) + ($invoice->prorata_amount ?? 0), 2, ',', ' ') }} €</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">TOTAL TTC :</td>
                <td class="value">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>

    <!-- Conditions et notes -->
    <div class="payment-terms">
        <strong>Modalités de paiement :</strong><br>
        Virement bancaire sur le compte de l'entreprise. Les chèques sans provision seront frappés de pénalités conformément à la loi.
    </div>
    <div style="clear: both;"></div>

    <!-- Pied de page -->
    <div class="footer">
        <div>{{ $company->name }} - SIRET: {{ $company->siret }} - Généré le {{ $generated_at }}</div>
        <div>Facture de référence : {{ $invoice->reference }}</div>
        <div class="footer-note">
            Conformément aux dispositions légales, cette facture a été légalisée selon la norme NF525.
        </div>
    </div>
@endsection
