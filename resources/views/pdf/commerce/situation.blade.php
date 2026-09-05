@extends('pdf.layout')

@section('header_right')
    <div class="situation-info">
        <div class="label">SITUATION DE TRAVAUX</div>
        <div class="value">N°{{ $situation->number }}</div>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 5px 0;">
        <div style="font-size: 9px; margin-top: 10px;">
            <div><strong>Date :</strong> {{ $situation->created_at->format('d/m/Y') }}</div>
            {{-- La période peut être calculée ou stockée sur le modèle Situation --}}
            <div><strong>Période :</strong> {{ $situation->periode_start->format('d/m/Y') }} au {{ $situation->periode_end->format('d/m/Y') }}</div>
            <div><strong>Commande Client :</strong> {{ $situation->order->reference }}</div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .details-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
        margin-top: 20px;
        border-radius: 10%;
    }
    .details-table th, .details-table td {
        border: 1px solid #ddd;
        padding: 6px;
        text-align: left;
    }
    .details-table th {
        background-color: #1e40af;
        font-weight: bold;
    }
    .details-table th:first-child { border-top-left-radius: 8px; }
    .details-table th:last-child { border-top-right-radius: 8px; }
    .details-table tr:last-child td:first-child { border-bottom-left-radius: 8px; }
    .details-table tr:last-child td:last-child { border-bottom-right-radius: 8px; }
    .details-table td.text-right { text-align: right; }
    .details-table td.text-center { text-align: center; }
    .totals-section {
        width: 50%;
        margin-top: 20px;
        margin-left: 50%;
        font-size: 10px;
    }
    .totals-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    .totals-table td {
        padding: 5px;
        border: 1px solid #ddd;
    }

    .totals-table tr:first-child td:first-child { border-top-left-radius: 8px; }
    .totals-table tr:first-child td:last-child { border-top-right-radius: 8px; }
    .totals-table tr:last-child td:first-child { border-bottom-left-radius: 8px; }
    .totals-table tr:last-child td:last-child { border-bottom-right-radius: 8px; }

    .totals-table td.label {
        font-weight: bold;
        width: 60%;
    }
    .totals-table td.value {
        text-align: right;
    }
    .totals-table tr.total-row td {
        font-weight: bold;
        background-color: #f2f2f2;
        border-top: 2px solid #333;
    }
    .address-section {
        margin-top: 20px;
        font-size: 10px;
        display: flex;
        justify-content: space-between;
    }
    .address-block {
        width: 48%;
        border: 1px solid #eee;
        padding: 10px;
        border-radius: 8px;
    }
    .address-block .title {
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 11px;
    }
</style>
@endsection

@section('content')
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="address-block">
                    <div class="title">Client</div>
                    <strong>{{ $situation->order->client->name }}</strong><br>
                    {{ $situation->order->client->address }}<br>
                    {{ $situation->order->client->zip }} {{ $situation->order->client->city }}
                    @if($situation->order->client->addresses->first())
                        {{ $situation->order->client->addresses->first()->street }}<br>
                        {{ $situation->order->client->addresses->first()->zip_code }} {{ $situation->order->client->addresses->first()->city }}<br>
                    @endif
                    @if($situation->order->client->phone)
                        Tél: {{ $situation->order->client->phone }}<br>
                    @endif
                    @if($situation->order->client->email)
                        Email: {{ $situation->order->client->email }}
                    @endif
                </div>
            </td>
            <td style="width: 4%;"></td> {{-- Espaceur --}}
            <td style="width: 48%; vertical-align: top;">
                <div class="address-block">
                    <div class="title">Chantier</div>
                    @if($situation->chantier)
                        <strong>{{ $situation->chantier->name }}</strong><br>
                        {{ $situation->chantier->address }}<br>
                        {{ $situation->chantier->zip }} {{ $situation->chantier->city }}
                    @else
                        Adresse non spécifiée.
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Tableau principal des lignes de la situation --}}
    <table class="details-table">
        <thead>
        <tr>
            <th>Désignation</th>
            <th class="text-center">Qté Prévue</th>
            <th class="text-right">P.U. HT</th>
            <th class="text-right">Montant Marché HT</th>
            <th class="text-center">Avancement Cumulé</th>
            <th class="text-right">Montant Période HT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($situation->items as $situationItem)
            @php
                // Le modèle CustomerSituationItem a une relation "item()" qui pointe vers CustomerOrderItem.
                // C'est un peu confus mais c'est la structure.
                $orderItem = $situationItem->item;
                $totalLigne = $orderItem->quantity * $orderItem->selling_price;
            @endphp
            <tr>
                <td>{{ $orderItem->name }}</td>
                <td class="text-center">{{ number_format($orderItem->quantity, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($orderItem->selling_price, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($totalLigne, 2, ',', ' ') }} €</td>
                <td class="text-center">{{ number_format($situationItem->progress_percentage, 2, ',', ' ') }} %</td>
                <td class="text-right">{{ number_format($situationItem->amount_ht, 2, ',', ' ') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- Section des totaux et récapitulatif --}}
    {{-- Section des totaux et récapitulatif (avec layout par tableau pour la compatibilité PDF) --}}
    <table style="width: 100%; margin-top: 20px; page-break-inside: avoid;">
        <tbody>
        <tr>
            <td style="width: 50%;"></td> {{-- Colonne vide pour pousser le contenu à droite --}}
            <td style="width: 50%; vertical-align: top;">
                @php
                    // Calcul des totaux pour l'affichage
                    $totalMarche = $situation->order->total_ht;
                    $montantPeriodeBrut = $situation->total_ht + $situation->retenue_garantie_amount + $situation->prorata_amount;
                    $montantCumule = $situation->getAmountBilledBefore() + $montantPeriodeBrut;
                    $avancementGlobal = ($totalMarche > 0) ? ($montantCumule / $totalMarche) * 100 : 0;
                    $totalTva = $situation->total_ht * 0.2; // A adapter si vous avez une gestion de la TVA plus complexe
                    $totalTtc = $situation->total_ht + $totalTva;
                @endphp
                <table class="totals-table">
                    <tr>
                        <td class="label">Total des travaux de la période HT</td>
                        <td class="value">{{ number_format($montantPeriodeBrut, 2, ',', ' ') }} €</td>
                    </tr>
                    @if($situation->retenue_garantie_amount > 0)
                        <tr>
                            <td class="label">Dont Retenue de Garantie (5%)</td>
                            <td class="value">- {{ number_format($situation->retenue_garantie_amount, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    @if($situation->prorata_amount > 0)
                        <tr>
                            <td class="label">Dont Compte Prorata</td>
                            <td class="value">- {{ number_format($situation->prorata_amount, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">NET de la période HT</td>
                        <td class="value">{{ number_format($situation->total_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Montant TVA (20%)</td>
                        <td class="value">{{ number_format($totalTva, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">NET A PAYER TTC</td>
                        <td class="value">{{ number_format($totalTtc, 2, ',', ' ') }} €</td>
                    </tr>
                </table>

                {{-- Tableau récapitulatif du marché --}}
                <h4 style="margin-top: 20px; font-size: 11px; margin-bottom: 5px;">Récapitulatif du Marché</h4>
                <table class="totals-table">
                    <tr>
                        <td class="label">Total Marché initial HT</td>
                        <td class="value">{{ number_format($totalMarche, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Montant situations antérieures HT</td>
                        <td class="value">{{ number_format($situation->getAmountBilledBefore(), 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Montant situation actuelle HT</td>
                        <td class="value">{{ number_format($montantPeriodeBrut, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">Montant Cumulé des Travaux HT</td>
                        <td class="value">{{ number_format($montantCumule, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Avancement Global du Marché</td>
                        <td class="value">{{ number_format($avancementGlobal, 2, ',', ' ') }} %</td>
                    </tr>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    {{-- Note: Il est recommandé d'ajouter une méthode getAmountBilledBefore() au modèle CustomerSituation --}}
    {{-- pour encapsuler la logique de calcul du montant total des situations précédentes. --}}
    {{-- Exemple:
        public function getAmountBilledBefore(): float {
            return self::where('customer_order_id', $this->customer_order_id)
                       ->where('number', '<', $this->number)
                       ->get()
                       ->sum(function($situation) {
                           return $situation->total_ht + $situation->retenue_garantie_amount + $situation->prorata_amount;
                       });
        }
    --}}
@endsection
