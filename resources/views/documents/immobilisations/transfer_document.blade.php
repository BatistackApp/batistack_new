<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Transport</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details-table th { background-color: #f3f4f6; width: 30%; }
        .signatures { margin-top: 50px; width: 100%; display: table; }
        .signature-box { display: table-cell; width: 33%; text-align: center; }
        .signature-line { margin-top: 50px; border-top: 1px solid #000; width: 80%; margin-left: 10%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Bon de Transport - Transfert d'Équipement</div>
        <p>Transfert N° {{ $transfer->id }} - Date prévue : {{ $transfer->transfer_date->format('d/m/Y') }}</p>
    </div>

    <h3>Informations de l'Actif</h3>
    <table class="details-table">
        <tr>
            <th>Nom de l'actif</th>
            <td>{{ $transfer->fixedAsset->name }}</td>
        </tr>
        <tr>
            <th>Catégorie</th>
            <td>{{ $transfer->fixedAsset->category->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>N° Série / Immatriculation</th>
            <td>{{ $transfer->fixedAsset->serial_number ?? 'N/A' }}</td>
        </tr>
    </table>

    <h3>Trajet</h3>
    <table class="details-table">
        <tr>
            <th>Chantier d'origine</th>
            <td>{{ optional($transfer->fromChantier)->name ?? 'Dépôt Central' }}</td>
        </tr>
        <tr>
            <th>Chantier de destination</th>
            <td>{{ optional($transfer->toChantier)->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Demandé par</th>
            <td>{{ optional($transfer->requester)->name ?? 'N/A' }}</td>
        </tr>
    </table>

    @if($transfer->notes)
    <h3>Notes</h3>
    <p>{{ $transfer->notes }}</p>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <p><strong>Départ (Responsable origine)</strong></p>
            <p>Date : ___/___/20__</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <p><strong>Transporteur</strong></p>
            <p>Date : ___/___/20__</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <p><strong>Arrivée (Responsable destination)</strong></p>
            <p>Date : ___/___/20__</p>
            <div class="signature-line"></div>
        </div>
    </div>
</body>
</html>
