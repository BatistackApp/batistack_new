<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquette QR</title>
    <style>
        @page { margin: 0; size: A4; }
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; background-color: white; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .label { border: 2px dashed #9ca3af; border-radius: 12px; padding: 40px; text-align: center; width: 60%; }
        .brand { font-size: 10px; letter-spacing: 2px; color: #9ca3af; text-transform: uppercase; margin-bottom: 8px; }
        .name { font-weight: bold; color: #111827; text-transform: uppercase; font-size: 20px; margin-bottom: 4px; }
        .serial { color: #6b7280; font-size: 13px; margin-bottom: 16px; }
        .qr { margin: 16px 0; }
        .footer { font-size: 10px; color: #9ca3af; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="label">
        <div class="brand">Batistack</div>
        <div class="name">{{ $asset->name ?? $asset->getLabel() }}</div>
        <div class="serial">N° série : {{ $asset->serial_number }}</div>
        <div class="qr"><img src="{{ $qrCode }}" alt="QR Code"></div>
        <div class="footer">Scannez ce QR code pour déclarer une casse / sinistre</div>
    </div>
</body>
</html>