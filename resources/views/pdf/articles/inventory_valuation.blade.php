<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #333; margin: 30px; }
        .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { max-width: 150px; max-height: 60px; margin-bottom: 10px; }
        .company-info { font-size: 11px; color: #64748b; }
        h1 { font-size: 18px; color: #0f172a; margin: 0 0 10px 0; }
        .meta-info { margin-bottom: 20px; font-size: 12px; }
        .meta-info p { margin: 2px 0; }
        
        .summary-box { background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; font-size: 14px; }
        .summary-box strong { color: #1e293b; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 11px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .footer { margin-top: 40px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        @if($company && $company->logo_path)
            <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="Logo" class="logo">
        @else
            <h1>{{ $company ? $company->name : 'Entreprise' }}</h1>
        @endif
        <div class="company-info">
            {{ $company ? $company->address : '' }}<br>
            {{ $company ? $company->zip_code . ' ' . $company->city : '' }}
        </div>
    </div>

    <h1>{{ $title }}</h1>

    <div class="meta-info">
        <p><strong>Date d'extraction :</strong> {{ $generated_at }}</p>
    </div>

    <div class="summary-box">
        VALEUR TOTALE DE L'INVENTAIRE : <strong>{{ number_format($totalValue, 2, ',', ' ') }} € HT</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Désignation</th>
                <th>Dépôt</th>
                <th class="text-center">Quantité</th>
                <th class="text-right">PUMP Unitaire</th>
                <th class="text-right">Valeur Totale</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $stock)
            <tr>
                <td>{{ $stock->item->reference }}</td>
                <td>{{ $stock->item->name }}</td>
                <td>{{ $stock->warehouse->name }}</td>
                <td class="text-center">{{ number_format($stock->quantity, 2, ',', ' ') }} {{ $stock->item->unit->symbol ?? '' }}</td>
                <td class="text-right">{{ number_format($stock->item->purchase_price ?? 0, 2, ',', ' ') }} €</td>
                <td class="text-right"><strong>{{ number_format($stock->quantity * ($stock->item->purchase_price ?? 0), 2, ',', ' ') }} €</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document comptable généré automatiquement par Batistack le {{ $generated_at }} - Page 1
    </div>
</body>
</html>
