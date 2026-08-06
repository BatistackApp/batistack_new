<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquettes Dymo 28x89</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }
        @page {
            size: 89mm 28mm landscape;
            margin: 0;
        }
        .label {
            width: 89mm;
            height: 28mm;
            box-sizing: border-box;
            padding: 2mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            page-break-after: always;
        }
        .content {
            flex-grow: 1;
            padding-right: 2mm;
            overflow: hidden;
        }
        .item-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-ref {
            font-size: 11px;
            color: #333;
        }
        .qr-code {
            width: 24mm;
            height: 24mm;
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
                <div class="item-ref">{{ $label['item']->barcode ? 'C-B: '.$label['item']->barcode : '' }}</div>
            </div>
            <img src="{{ $label['qrCode'] }}" class="qr-code" alt="QR Code">
        </div>
    @endforeach
</body>
</html>
