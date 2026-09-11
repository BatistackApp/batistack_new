@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">{{ $title }}</div>
        <div class="value">{{ $delivery->reference }}</div>
        <div style="margin-top: 8px;">
            <span class="label">Date de livraison :</span>
            <span class="value">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d/m/Y') : $generated_at }}</span>
        </div>
        @if($delivery->order)
            <div>
                <span class="label">Commande d'origine :</span>
                <span class="value">{{ $delivery->order->reference }}</span>
            </div>
        @endif
        @if($delivery->order?->quote)
            <div>
                <span class="label">Devis :</span>
                <span class="value">{{ $delivery->order->quote->reference }}</span>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="section-title">CLIENT</div>
        <div class="section-content">
            <strong>{{ $delivery->client->name }}</strong><br>
            @if($delivery->client->address)
                {{ $delivery->client->address }}<br>
            @endif
            @if($delivery->client->zip_code && $delivery->client->city)
                {{ $delivery->client->zip_code }} {{ $delivery->client->city }}<br>
            @endif
            @if($delivery->client->phone)
                Tél : {{ $delivery->client->phone }}<br>
            @endif
            @if($delivery->client->email)
                Email : {{ $delivery->client->email }}
            @endif
        </div>
    </div>

    @php
        $hasPartialDelivery = $delivery->lines->contains(fn ($line) => $line->quantity_delivered < $line->quantity);
    @endphp

    @if($hasPartialDelivery)
        <div style="background-color: #fef3c7; border: 1px solid #f59e0b; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #92400e;">
            LIVRAISON PARTIELLE
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIPTION</th>
                <th>MATÉRIAU</th>
                <th>DIMENSIONS (mm)</th>
                <th>ÉP. (mm)</th>
                <th>QTÉ CMD</th>
                <th>QTÉ LIVRÉE</th>
                <th>POIDS (kg)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($delivery->lines as $line)
                <tr>
                    <td>{{ $line->description ?? 'Pièce '.$loop->iteration }}</td>
                    <td>{{ $line->material->name ?? '-' }}</td>
                    <td>{{ number_format($line->length_mm, 0) }} × {{ number_format($line->width_mm, 0) }}</td>
                    <td>{{ number_format($line->thickness_mm, 1) }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>
                        <strong>{{ $line->quantity_delivered }}</strong>
                        @if($line->quantity_delivered < $line->quantity)
                            <br><small style="color: #92400e;">Partiel</small>
                        @endif
                    </td>
                    <td>{{ number_format($line->weight_kg, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Aucune ligne</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="clear: both; margin-top: 20px;">
        <div class="section-title">OBSERVATIONS / RÉSERVES</div>
        <div class="section-content" style="min-height: 60px; border: 1px solid #e2e8f0; padding: 8px; margin-top: 5px;">
            @if($delivery->status->value === 'draft')
                <em style="color: #999;">À compléter avant expédition...</em>
            @endif
        </div>
    </div>

    <div style="clear: both; margin-top: 30px; display: flex; justify-content: space-between;">
        <div style="width: 45%;">
            <div class="section-title">SIGNATURE DU CLIENT</div>
            <div style="min-height: 80px; border-bottom: 1px solid #000; margin-top: 40px;"></div>
            <div style="font-size: 9px; color: #666; margin-top: 5px;">Date et signature pour réception</div>
        </div>
        <div style="width: 45%;">
            <div class="section-title">CACHET DE L'ENTREPRISE</div>
            <div style="min-height: 80px; border: 1px dashed #ccc; margin-top: 10px; display: flex; align-items: center; justify-content: center;">
                <span style="color: #ccc; font-size: 10px;">Cachet</span>
            </div>
        </div>
    </div>
@endsection
