<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquettes Dymo 59x190</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }
        @page {
            size: 190mm 59mm landscape;
            margin: 0;
        }
        .label {
            width: 190mm;
            height: 59mm;
            box-sizing: border-box;
            padding: 5mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            page-break-after: always;
        }
        .content {
            flex-grow: 1;
            padding-right: 5mm;
            overflow: hidden;
        }
        .item-name {
            font-weight: bold;
            font-size: 24px;
            margin-bottom: 3mm;
        }
        .item-ref {
            font-size: 16px;
            color: #333;
            margin-bottom: 2mm;
        }
        .qr-code {
            width: 49mm;
            height: 49mm;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    @foreach($labels as $label)
        <div class="label">
            <div class="content">
                <div class="item-name">{{ $label['item']->name }}</div>
                <div class="item-ref">Réf: {{ $label['item']->reference ?? 'N/A' }}</div>
                <div class="item-ref">{{ $label['item']->barcode ? 'Code-barres: '.$label['item']->barcode : '' }}</div>
            </div>
            <img src="{{ $label['qrCode'] }}" class="qr-code" alt="QR Code">
        </div>
    @endforeach
</body>
</html>
