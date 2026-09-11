@extends('pdf.layout')

@section('header_right')
    <div class="invoice-info">
        <div class="label">{{ $title }}</div>
        <div class="value">{{ $creditNote->reference }}</div>
        <div style="margin-top: 8px;">
            <span class="label">Date :</span>
            <span class="value">{{ $creditNote->created_at->format('d/m/Y') }}</span>
        </div>
        @if($creditNote->invoice)
            <div>
                <span class="label">Facture d'origine :</span>
                <span class="value">{{ $creditNote->invoice->reference }}</span>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="client-section">
        <div class="section-title">CLIENT</div>
        <div class="section-content">
            <strong>{{ $creditNote->client->name }}</strong><br>
            @if($creditNote->client->address)
                {{ $creditNote->client->address }}<br>
            @endif
            @if($creditNote->client->zip_code && $creditNote->client->city)
                {{ $creditNote->client->zip_code }} {{ $creditNote->client->city }}<br>
            @endif
            @if($creditNote->client->phone)
                Tél : {{ $creditNote->client->phone }}<br>
            @endif
            @if($creditNote->client->email)
                Email : {{ $creditNote->client->email }}
            @endif
        </div>
    </div>

    @if($creditNote->reason)
        <div style="margin-bottom: 15px;">
            <div class="section-title">MOTIF</div>
            <div class="section-content">{{ $creditNote->reason }}</div>
        </div>
    @endif

    <div style="margin-top: 20px;">
        <div style="float: right; width: 300px;">
            <table style="width: 100%; font-size: 11px;">
                <tr>
                    <td style="padding: 4px 0;"><strong>Total HT :</strong></td>
                    <td style="text-align: right; padding: 4px 0;">{{ number_format($creditNote->total_ht, 2) }} €</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>TVA ({{ $creditNote->vat_rate }}%) :</strong></td>
                    <td style="text-align: right; padding: 4px 0;">{{ number_format($creditNote->total_tva, 2) }} €</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td style="padding: 8px 0; font-size: 13px;"><strong>TOTAL TTC :</strong></td>
                    <td style="text-align: right; padding: 8px 0; font-size: 13px;"><strong>{{ number_format($creditNote->total_ttc, 2) }} €</strong></td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-top: 30px;">
        <div class="section-title">MENTIONS LÉGALES</div>
        <div class="section-content" style="font-size: 9px; color: #666;">
            <p>Avoir émis conformément aux dispositions relatives à la facturation.</p>
            <p>Avoir n° {{ $creditNote->reference }} émis le {{ $creditNote->created_at->format('d/m/Y') }}.</p>
            @if($creditNote->invoice)
                <p>Avoir rattaché à la facture n° {{ $creditNote->invoice->reference }}.</p>
            @endif
            @if($creditNote->signature_hash)
                <p>Empreinte SHA-256 : {{ $creditNote->signature_hash }}</p>
            @endif
        </div>
    </div>
@endsection
