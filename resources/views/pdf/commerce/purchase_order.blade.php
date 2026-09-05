@extends('pdf.layout')

@section('header_right')
    <div class="quote-info">
        <div class="label">BON DE COMMANDE FOURNISSEUR</div>
        <div class="value">{{ $order->reference }}</div>
        <div style="font-size: 10px; text-align: right;">
            <div><strong>Date :</strong> {{ $order->ordered_at?->format('d/m/Y') ?? $order->created_at?->format('d/m/Y') }}</div>
            <div><strong>Statut :</strong> {{ $order->status?->getLabel() ?? $order->status }}</div>
            @if($order->expected_delivery_date)
                <div><strong>Livraison prévue :</strong> {{ $order->expected_delivery_date->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="client-info">
            <div class="section-title">FOURNISSEUR</div>
            <div class="section-content">
                <strong>{{ $order->supplier?->name }}</strong><br>
                @php $supplierAddress = $order->supplier?->getMainAddress(); @endphp
                @if($supplierAddress)
                    {{ $supplierAddress->street }}<br>
                    {{ $supplierAddress->zip_code }} {{ $supplierAddress->city }}
                @endif
                @if($order->supplier?->phone)
                    Tél: {{ $order->supplier->phone }}<br>
                @endif
                @if($order->supplier?->email)
                    Email: {{ $order->supplier->email }}
                @endif
            </div>
        </div>

        <div class="project-info">
            <div class="section-title">CHANTIER</div>
            <div class="section-content">
                @if($order->chantier)
                    <strong>{{ $order->chantier->reference }}</strong><br>
                    {{ $order->chantier->address }}<br>
                    {{ $order->chantier->zip_code }} {{ $order->chantier->city }}
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
            <th class="qte">UNIT</th>
            <th class="price">PU HT</th>
            <th class="total">TOTAL HT</th>
            <th style="width: 10%;">TVA</th>
        </tr>
        </thead>
        <tbody>
        @forelse($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->item?->name ?? $item->name }}</strong>
                    @if($item->item?->description)
                        <div style="font-size: 9px; color: #6b7280;">{!! nl2br(e($item->item->description)) !!}</div>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="text-center">{{ $item->item?->unit?->symbol ?? '–' }}</td>
                <td class="text-right">{{ number_format($item->price_unit_ht, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item->quantity * $item->price_unit_ht, 2, ',', ' ') }} €</td>
                <td class="text-center">{{ number_format($item->vatRate?->rate ?? 0, 0) }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center"><em>Aucun article</em></td>
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
                            <td class="value">{{ number_format($order->total_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr>
                            <td class="label">TOTAL TVA</td>
                            <td class="value">{{ number_format(max($order->total_ttc - $order->total_ht, 0), 2, ',', ' ') }} €</td>
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
            <div class="conditions-title">Conditions de paiement</div>
            <div class="conditions-content">
                Paiement à réception de facture, selon les conditions convenues avec le fournisseur.
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 11px;">
            <p>Nous vous remercions pour votre collaboration.</p>
        </div>
    </div>
@endsection