@extends('pdf.layout')

@section('header_right')
    <div class="quote-info">
        <div class="label">DEVIS</div>
        <div class="value">{{ $quote->reference }}</div>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 5px 0;">
        <div style="font-size: 10px; margin-top: 10px;">
            <div><strong>Date :</strong> {{ $quote->created_at->format('d/m/Y') }}</div>
            <div><strong>Valide jusqu'au :</strong> {{ $quote->expires_at->format('d/m/Y') }}</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="client-info">
            <div class="section-title">CLIENT</div>
            <div class="section-content">
                <strong>{{ $quote->client->name }}</strong><br>
                @if($quote->client->addresses->first())
                    {{ $quote->client->addresses->first()->street }}<br>
                    {{ $quote->client->addresses->first()->zip_code }} {{ $quote->client->addresses->first()->city }}<br>
                @endif
                @if($quote->client->phone)
                    Tél: {{ $quote->client->phone }}<br>
                @endif
                @if($quote->client->email)
                    Email: {{ $quote->client->email }}
                @endif
            </div>
        </div>

        <div class="project-info">
            <div class="section-title">CHANTIER</div>
            <div class="section-content">
                @if($quote->chantier)
                    <strong>{{ $quote->chantier->reference }}</strong><br>
                    {{ $quote->chantier->address }}<br>
                    {{ $quote->chantier->zip_code }} {{ $quote->chantier->city }}<br>
                @else
                    <em>Aucun chantier associé</em>
                @endif
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 40%;">DESCRIPTION</th>
            <th class="qty">QTÉ</th>
            <th class="price">PRIX UNITAIRE</th>
            <th class="total">TOTAL HT</th>
            <th style="width: 10%;">TVA</th>
        </tr>
        </thead>
        <tbody>
        @forelse($quote->items as $item)
            <tr>
                <td><strong>{{ $item->name }}</strong></td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($item->selling_price, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item->quantity * $item->selling_price, 2, ',', ' ') }} €</td>
                <td class="text-center">{{ $item->vatRate->rate ?? 'N/A' }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center"><em>Aucun article</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">TOTAL HT :</td>
                <td class="value">{{ number_format($quote->total_ht, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="label">TOTAL TVA :</td>
                <td class="value">{{ number_format($quote->total_ttc - $quote->total_ht, 2, ',', ' ') }} €</td>
            </tr>
            <tr class="total-row">
                <td class="label">TOTAL TTC :</td>
                <td class="value">{{ number_format($quote->total_ttc, 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>

    <!-- Conditions -->
    @if($quote->notes || $quote->terms)
        <div class="conditions">
            <div class="conditions-title">CONDITIONS ET REMARQUES</div>
            <div class="conditions-content">
                @if($quote->notes)
                    <strong>Notes :</strong> {{ $quote->notes }}<br><br>
                @endif
                @if($quote->terms)
                    <strong>Conditions de paiement :</strong> {{ $quote->terms }}
                @else
                    <strong>Conditions de paiement :</strong> 30 jours net dès facture
                @endif
            </div>
        </div>
    @endif
    <!-- Pied de page -->
    <div class="footer">
        <div>{{ $company->name }} - {{ $company->siret }} - Généré le {{ $generated_at }}</div>
        <div>Ce devis est valable jusqu'au {{ $quote->expires_at->format('d/m/Y') }}</div>
    </div>
@endsection
