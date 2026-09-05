@extends('pdf.layout')

@section('header_right')
    <div class="quote-info">
        <div class="label">BON DE LIVRAISON</div>
        <div class="value">{{ $delivery->reference }}</div>
        <div style="font-size: 10px; text-align: right;">
            <div><strong>Date :</strong> {{ $delivery->created_at->format('d/m/Y') }}</div>
            @if($delivery->order)
                <div><strong>Commande N° :</strong> {{ $delivery->order->reference }}</div>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="client-info">
            <div class="section-title">CLIENT</div>
            <div class="section-content">
                <strong>{{ $delivery->client->name }}</strong><br>
                @if($delivery->client->addresses->first())
                    {{ $delivery->client->addresses->first()->street }}<br>
                    {{ $delivery->client->addresses->first()->zip_code }} {{ $delivery->client->addresses->first()->city }}<br>
                @endif
                @if($delivery->client->phone)
                    Tél: {{ $delivery->client->phone }}<br>
                @endif
                @if($delivery->client->email)
                    Email: {{ $delivery->client->email }}
                @endif
            </div>
        </div>

        <div class="project-info">
            <div class="section-title">CHANTIER DE LIVRAISON</div>
            <div class="section-content">
                @if($delivery->chantier)
                    <strong>{{ $delivery->chantier->reference }}</strong><br>
                    {{ $delivery->chantier->address }}<br>
                    {{ $delivery->chantier->zip_code }} {{ $delivery->chantier->city }}<br>
                @else
                    <em>Aucun chantier associé</em>
                @endif
            </div>
        </div>
    </div>

    <div class="object-section" style="margin: 30px 0;">
        <p><strong>Objet : Bon de livraison N°{{ $delivery->reference }}</strong></p>
    </div>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 70%;">DESCRIPTION</th>
            <th class="qty" style="width: 15%;">QTÉ CMDEE</th>
            <th class="qty" style="width: 15%;">QTÉ LIVRÉE</th>
            <th class="qte" style="width: 15%;">UNIT</th>
        </tr>
        </thead>
        <tbody>
        @forelse($delivery->items as $item)
            @php
                $orderedQuantity = 'N/A';
                if ($delivery->order) {
                    // Recherche l'article correspondant dans la commande pour obtenir la quantité commandée.
                    // On se base sur la liaison via l'ID du produit (item_id).
                    $orderItem = $delivery->order->items->firstWhere('item_id', $item->item_id);
                    if ($orderItem) {
                        $orderedQuantity = number_format($orderItem->quantity, 2, ',', ' ');
                    }
                }
            @endphp
            <tr>
                <td>
                    <strong>{{ $item->item->name }}</strong>
                    @if($item->item && $item->item->description)
                        <div style="font-size: 9px; color: #6b7280;">{!! nl2br(e($item->item->description)) !!}</div>
                    @endif
                </td>
                <td class="text-center">{{ $orderedQuantity }}</td>
                <td class="text-center">{{ number_format($item->quantity_delivered, 2, ',', ' ') }}</td>
                <td class="text-center">{{ $item->item->unit->symbol ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center"><em>Aucun article sur ce bon de livraison.</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="end-section" style="page-break-inside: avoid; margin-top: 50px;">
        <div class="reception-notes" style="border: 1px solid #ddd; padding: 10px; min-height: 80px; margin-bottom: 40px;">
            <p style="font-weight: bold; font-size: 11px; margin-bottom: 5px;">Observations / Réserves :</p>
        </div>
    </div>
@endsection
