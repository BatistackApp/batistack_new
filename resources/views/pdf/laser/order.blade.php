@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">{{ $title }}</div>
        <div class="value">{{ $order->reference }}</div>
        <div style="margin-top: 8px;">
            <span class="label">Date :</span>
            <span class="value">{{ $generated_at }}</span>
        </div>
        @if($order->quote)
            <div>
                <span class="label">Suite à votre devis :</span>
                <span class="value">{{ $order->quote->reference }}</span>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="section-title">CLIENT</div>
        <div class="section-content">
            <strong>{{ $order->client->name }}</strong><br>
            @if($order->client->address)
                {{ $order->client->address }}<br>
            @endif
            @if($order->client->zip_code && $order->client->city)
                {{ $order->client->zip_code }} {{ $order->client->city }}<br>
            @endif
            @if($order->client->phone)
                Tél : {{ $order->client->phone }}<br>
            @endif
            @if($order->client->email)
                Email : {{ $order->client->email }}
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIPTION</th>
                <th>MATÉRIAU</th>
                <th>DIMENSIONS (mm)</th>
                <th>ÉP. (mm)</th>
                <th>QTÉ</th>
                <th>POIDS (kg)</th>
                <th>PRIX UNIT. HT</th>
                <th>REMISE</th>
                <th>TOTAL HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->lines as $line)
                <tr>
                    <td>
                        {{ $line->description ?? 'Pièce '.$loop->iteration }}
                        @if($line->programming_cost > 0)
                            <br><small>Programmation : {{ number_format($line->programming_cost, 2, ',', ' ') }} €</small>
                        @endif
                    </td>
                    <td>{{ $line->material->name ?? '-' }}</td>
                    <td>{{ number_format($line->length_mm, 0) }} × {{ number_format($line->width_mm, 0) }}</td>
                    <td>{{ number_format($line->thickness_mm, 1) }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format($line->weight_kg, 2) }}</td>
                    <td>{{ number_format($line->unit_price_ht, 2) }} €</td>
                    <td>{{ $line->discount_pct > 0 ? number_format($line->discount_pct, 0).'%' : '-' }}</td>
                    <td>{{ number_format($line->total_ht, 2) }} €</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Aucune ligne</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-section">
        <table>
            <tr>
                <td>TOTAL HT</td>
                <td style="text-align: right;">{{ number_format($order->total_ht, 2) }} €</td>
            </tr>
            <tr>
                <td>TVA 20%</td>
                <td style="text-align: right;">{{ number_format($order->total_ttc - $order->total_ht, 2) }} €</td>
            </tr>
            <tr style="font-weight: bold; font-size: 1.1em;">
                <td>TOTAL TTC</td>
                <td style="text-align: right;">{{ number_format($order->total_ttc, 2) }} €</td>
            </tr>
        </table>
    </div>

    @if($order->terms)
        <div class="conditions">
            <div class="section-title">CONDITIONS PARTICULIÈRES</div>
            <div class="section-content">{!! nl2br(e($order->terms)) !!}</div>
        </div>
    @endif

    <div class="conditions">
        <div class="section-title">CONDITIONS GÉNÉRALES</div>
        <div class="section-content">
            Paiement à 30 jours fin de mois. En cas de retard de paiement, des pénalités de retard au taux de 3 fois l'intérêt légal seront appliquées.
        </div>
    </div>
@endsection
