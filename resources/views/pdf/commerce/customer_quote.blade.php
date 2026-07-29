@extends('pdf.layout')

@section('header_right')
    <div class="quote-info">
        <div class="label" style="font-size: 22px; color: #1e40af; font-weight: bold; margin-bottom: 10px;">DEVIS</div>
        <div class="value" style="background-color: #f3f4f6; padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 14px; margin-bottom: 10px;">{{ $quote->reference }}</div>
        <div style="font-size: 10px; text-align: right;">
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

    <div class="object-section" style="margin: 30px 0;">
        <p><strong>Objet : {{ $quote->name }}</strong></p>
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
        @forelse($quote->items as $item)
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
                <td colspan="5" class="text-center"><em>Aucun article</em></td>
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
                            <td class="value">{{ number_format($quote->total_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr>
                            <td class="label">TOTAL TVA</td>
                            <td class="value">{{ number_format($quote->total_ttc - $quote->total_ht, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">TOTAL TTC</td>
                            <td class="value">{{ number_format($quote->total_ttc, 2, ',', ' ') }} €</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="conditions" style="margin-top: 40px;">
            @if($quote->terms)
                <div class="conditions-title" style="font-weight: bold; margin-bottom: 5px;">Conditions de paiement</div>
                <div class="conditions-content" style="margin-bottom: 15px;">
                    {{ $quote->terms }}
                </div>
            @else
                <div class="conditions-title" style="font-weight: bold; margin-bottom: 5px;">Conditions de paiement</div>
                <div class="conditions-content" style="margin-bottom: 15px;">
                    Acompte de 30% à la commande, solde à réception de facture.
                </div>
            @endif

                <div class="signature-section" style="margin-top: 50px; text-align: right;">
                    @php
                        // On ne cherche une signature que si l'objet devis existe.
                        if (isset($quote)) {
                            $signedSignature = $quote->signatures()
                                ->where('status', \App\Enums\Core\SignatureStatus::SIGNED)
                                ->latest('signed_at')
                                ->first();
                        } else {
                            $signedSignature = null;
                        }
                    @endphp

                    @if($signedSignature)
                        <div class="signature-details" style="font-size: 10px;">
                            <p style="font-weight: bold; margin-bottom: 10px;">Signé numériquement</p>
                            <p>Le : {{ $signedSignature->signed_at->format('d/m/Y à H:i') }}</p>
                            @if($signedSignature->user)
                                <p>Par : {{ $signedSignature->user->name }} ({{ $signedSignature->ip_address }})</p>
                            @else
                                <p>Par : Non identifié ({{ $signedSignature->ip_address }})</p>
                            @endif

                            @if ($signedSignature->type === \App\Enums\Core\SignatureType::AUTOGRAPH && $signedSignature->signature_data)
                                <div style="margin-top: 10px;">
                                    <img src="{{ $signedSignature->signature_data }}" alt="Signature" style="max-width: 120px; height: auto; border-bottom: 1px solid #333;">
                                </div>
                            @endif
                        </div>
                    @else
                        <div style="position: relative; display: inline-block; min-width: 250px; text-align: center;">
                            <p>Bon pour accord, le ___________________</p>
                            <p style="margin-top: 40px; margin-bottom: 20px;">(Signature et cachet)</p>
                            <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; color: transparent; font-size: 8px;">
                                @{{Signature;role=Signataire;type=signature}}
                            </div>
                        </div>
                    @endif
                </div>
        </div>
    </div>
@endsection
