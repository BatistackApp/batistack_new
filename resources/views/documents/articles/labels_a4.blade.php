<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquettes A4</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            font-size: 10px;
        }
        /* Format A4 (210 x 297 mm) */
        @page {
            size: A4;
            margin: 0;
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 15mm 5mm; /* Marges haut/bas 15mm, gauche/droite 5mm */
            box-sizing: border-box;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
        }
        /* Planche 3 colonnes x 7 lignes = 21 étiquettes. (Ex: Avery L7159 63.5 x 38.1 mm) */
        .label {
            width: 63.5mm; /* 210mm - 10mm marges = 200 / 3 = 66.6, on prend 63.5 standard */
            height: 38.1mm; /* 297mm - 30mm marges = 267 / 7 = 38.1 standard */
            box-sizing: border-box;
            padding: 2mm;
            text-align: center;
            overflow: hidden;
            display: inline-block;
            margin: 0 1.5mm; /* Espacement horizontal */
            /* Outline pour débug ou découpe, a retirer si besoin */
            border: 1px dashed #ccc; 
        }
        .qr-code {
            max-width: 20mm;
            max-height: 20mm;
            margin: 0 auto;
        }
        .item-name {
            font-weight: bold;
            font-size: 11px;
            margin-top: 2mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-ref {
            font-size: 9px;
            color: #555;
            margin-top: 1mm;
        }
        /* Page break tous les 21 labels */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $chunks = array_chunk($labels, 21);
    @endphp

    @foreach($chunks as $chunk)
        <div class="page {{ !$loop->last ? 'page-break' : '' }}">
            @foreach($chunk as $label)
                <div class="label">
                    <img src="{{ $label['qrCode'] }}" class="qr-code" alt="QR Code">
                    <div class="item-name">{{ $label['item']->name }}</div>
                    <div class="item-ref">Réf: {{ $label['item']->reference ?? 'N/A' }}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
