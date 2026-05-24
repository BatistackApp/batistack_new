@extends('pdf.layout')

@section('header_right')
    <div class="quote-info">
        <div class="label" style="font-size: 22px; color: #1e40af; font-weight: bold; margin-bottom: 10px;">BON DE COMMANDE</div>
        <div class="value" style="background-color: #f3f4f6; padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 14px; margin-bottom: 10px;">{{ $order->reference }}</div>
        <div style="font-size: 10px; text-align: right;">
            <div><strong>Date :</strong> {{ $order->created_at->format('d/m/Y') }}</div>
            @if($order->quote)
                <div><strong>Suite à votre devis :</strong> {{ $order->quote->reference }}</div>
            @endif
        </div>
    </div>
@endsection

@section('styles')
    <style>

    </style>
@endsection

@section('content')
    <div class="client-section">
        <div class="client-info">
            <div class="section-title">CLIENT</div>
            <div class="section-content">
                <strong>{{ $order->client->name }}</strong><br>
                @if($order->client->addresses->first())
                    {{ $order->client->addresses->first()->street }}<br>
                    {{ $order->client->addresses->first()->zip_code }} {{ $order->client->addresses->first()->city }}<br>
                @endif
                @if($order->client->phone)
                    Tél: {{ $order->client->phone }}<br>
                @endif
                @if($order->client->email)
                    Email: {{ $order->client->email }}
                @endif
            </div>
        </div>

        <div class="project-info">
            <div class="section-title">CHANTIER</div>
            <div class="section-content">
                @if($order->chantier)
                    <strong>{{ $order->chantier->reference }}</strong><br>
                    {{ $order->chantier->address }}<br>
                    {{ $order->chantier->zip_code }} {{ $order->chantier->city }}<br>
                @else
                    <em>Aucun chantier associé</em>
                @endif
            </div>
        </div>
    </div>

    <div class="object-section" style="margin: 30px 0;">
        <p><strong>Objet : Votre commande N°{{ $order->reference }}</strong></p>
    </div>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 40%;">DESCRIPTION</th>
            <th class="qty">QTÉ</th>
            <th class="qte">UNIT</th>
            <th class="price">PRIX UNITAIRE</th>
            <th class="total">TOTAL HT</th>
            <th style="width: 10%;">TVA</th>
        </tr>
        </thead>
        <tbody>
        @forelse($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->name }}</strong>
                    @if($item->item->description)
                        <div style="font-size: 9px; color: #6b7280;">{!! nl2br(e($item->item->description)) !!}</div>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="text-center">{{ $item->item->unit->symbol }}</td>
                <td class="text-right">{{ number_format($item->selling_price, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item->quantity * $item->selling_price, 2, ',', ' ') }} €</td>
                <td class="text-center">{{ \Illuminate\Support\Number::format($item->vatRate->rate, 0) ?? 'N/A' }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center"><em>Aucun article</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="end-section" style="page-break-inside: avoid;">
        <div class="summary-section">
            <div style="clear: both;">
                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td class="label">TOTAL HT</td>
                            <td class="value">{{ number_format($order->total_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr>
                            <td class="label">TOTAL TVA</td>
                            <td class="value">{{ number_format($order->total_ttc - $order->total_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">TOTAL TTC</td>
                            <td class="value">{{ number_format($order->total_ttc, 2, ',', ' ') }} €</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="conditions" style="margin-top: 40px;">
            @if($order->terms)
                <div class="conditions-title" style="font-weight: bold; margin-bottom: 5px;">Conditions de paiement</div>
                <div class="conditions-content" style="margin-bottom: 15px;">
                    {{ $order->terms }}
                </div>
            @else
                <div class="conditions-title" style="font-weight: bold; margin-bottom: 5px;">Conditions de paiement</div>
                <div class="conditions-content" style="margin-bottom: 15px;">
                    Acompte de 30% à la commande, solde à réception de facture.
                </div>
            @endif
        </div>

        <div style="margin-top: 40px; font-size: 11px;">
            <p>Nous vous remercions pour votre confiance.</p>
        </div>
    </div>
@endsection
