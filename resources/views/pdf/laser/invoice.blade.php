@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">{{ $title }}</div>
        <div class="value">{{ $invoice->reference }}</div>
        <div style="margin-top: 8px;">
            <span class="label">Date de facturation :</span>
            <span class="value">{{ $invoice->created_at->format('d/m/Y') }}</span>
        </div>
        @if($invoice->due_date)
            <div>
                <span class="label">Échéance :</span>
                <span class="value">{{ $invoice->due_date->format('d/m/Y') }}</span>
            </div>
        @endif
        @if($invoice->order)
            <div>
                <span class="label">Commande :</span>
                <span class="value">{{ $invoice->order->reference }}</span>
            </div>
        @endif
        @if($invoice->status->value === 'validated' || $invoice->status->value === 'paid')
            <div>
                <span class="label">Empreinte :</span>
                <span class="value" style="font-size: 8px;">{{ substr($invoice->signature_hash, 0, 16) }}...</span>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="section-title">CLIENT</div>
        <div class="section-content">
            <strong>{{ $invoice->client->name }}</strong><br>
            @if($invoice->client->address)
                {{ $invoice->client->address }}<br>
            @endif
            @if($invoice->client->zip_code && $invoice->client->city)
                {{ $invoice->client->zip_code }} {{ $invoice->client->city }}<br>
            @endif
            @if($invoice->client->phone)
                Tél : {{ $invoice->client->phone }}<br>
            @endif
            @if($invoice->client->email)
                Email : {{ $invoice->client->email }}
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
                <th>P.U. HT</th>
                <th>REMISE</th>
                <th>TOTAL HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description ?? 'Pièce '.$loop->iteration }}</td>
                    <td>{{ $line->material->name ?? '-' }}</td>
                    <td>{{ number_format($line->length_mm, 0) }} × {{ number_format($line->width_mm, 0) }}</td>
                    <td>{{ number_format($line->thickness_mm, 1) }}</td>
                    <td>{{ $line->quantity_invoiced }}</td>
                    <td>{{ number_format($line->weight_kg, 2) }}</td>
                    <td>{{ number_format($line->unit_price_ht, 2) }} €</td>
                    <td>
                        @if($line->discount_pct > 0)
                            -{{ number_format($line->discount_pct, 0) }}%
                        @else
                            -
                        @endif
                    </td>
                    <td><strong>{{ number_format($line->total_ht, 2) }} €</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Aucune ligne</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="clear: both; margin-top: 20px;">
        <div style="float: right; width: 300px;">
            <table style="width: 100%; font-size: 11px;">
                <tr>
                    <td style="padding: 4px 0;"><strong>Total HT :</strong></td>
                    <td style="text-align: right; padding: 4px 0;">{{ number_format($invoice->total_ht, 2) }} €</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>TVA ({{ $invoice->vat_rate }}%) :</strong></td>
                    <td style="text-align: right; padding: 4px 0;">{{ number_format($invoice->total_tva, 2) }} €</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td style="padding: 8px 0; font-size: 13px;"><strong>TOTAL TTC :</strong></td>
                    <td style="text-align: right; padding: 8px 0; font-size: 13px;"><strong>{{ number_format($invoice->total_ttc, 2) }} €</strong></td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-top: 30px;">
        <div class="section-title">MENTIONS LÉGALES</div>
        <div class="section-content" style="font-size: 9px; color: #666;">
            <p>Facture émise conformément aux dispositions relatives à la facturation.</p>
            @if($invoice->status->value === 'validated' || $invoice->status->value === 'paid')
                <p>Facture n° {{ $invoice->reference }} — Empreinte SHA-256 : {{ $invoice->signature_hash }}</p>
            @endif
        </div>
    </div>
@endsection
