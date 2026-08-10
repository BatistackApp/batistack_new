<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bilan Carbone (RSE)</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; margin: 0; padding: 20px; }
        h1 { color: #111827; text-align: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .header-info { text-align: center; margin-bottom: 30px; color: #6b7280; }
        .summary-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 30px; }
        .summary-box h2 { margin: 0; font-size: 16px; color: #6b7280; }
        .summary-box p.big { font-size: 28px; font-weight: bold; color: #111827; margin: 10px 0; }
        .section { margin-bottom: 30px; }
        h3 { color: #374151; font-size: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #f3f4f6; }
        th { background: #f9fafb; font-weight: bold; color: #374151; }
        .right { text-align: right; }
    </style>
</head>
<body>

    <h1>Bilan Carbone de la Flotte</h1>
    <div class="header-info">
        Période du {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
    </div>

    <div class="summary-box">
        <h2>Émissions Totales (Équivalent CO2)</h2>
        <p class="big">{{ number_format($totalCo2Kg / 1000, 2) }} Tonnes</p>
        <p style="color: #6b7280; margin: 0; font-size: 14px;">Soit {{ number_format($totalCo2Kg, 0, ',', ' ') }} kg</p>
    </div>

    <div class="section">
        <h3>Répartition par Mois</h3>
        @if(empty($byMonth))
            <p>Aucune donnée pour cette période.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th class="right">Émissions (Tonnes)</th>
                        <th class="right">% du Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byMonth as $month => $kg)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</td>
                        <td class="right">{{ number_format($kg / 1000, 2) }} T</td>
                        <td class="right">{{ $totalCo2Kg > 0 ? number_format(($kg / $totalCo2Kg) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h3>Répartition par Chantier</h3>
        @if(empty($byChantier))
            <p>Aucune donnée affectée aux chantiers pour cette période.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Chantier</th>
                        <th class="right">Émissions (Tonnes)</th>
                        <th class="right">% du Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byChantier as $chantier)
                    <tr>
                        <td>{{ $chantier['name'] }}</td>
                        <td class="right">{{ number_format($chantier['total_kg'] / 1000, 2) }} T</td>
                        <td class="right">{{ $totalCo2Kg > 0 ? number_format(($chantier['total_kg'] / $totalCo2Kg) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</body>
</html>
