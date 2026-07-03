<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .company-info { width: 50%; }
        .employee-info { width: 50%; text-align: right; }
        h1 { font-size: 18px; color: #1a56db; text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e5e7eb; }
        .footer { text-align: center; font-size: 10px; color: #666; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <strong>{{ $company->legal_name }}</strong><br>
            {{ $company->address }}<br>
            {{ $company->zip_code }} {{ $company->city }}<br>
            SIRET: {{ $company->siret }}
        </div>
        <div class="employee-info">
            <strong>{{ $employee->full_name }}</strong><br>
            Matricule: {{ $employee->registration_number }}<br>
            Poste: {{ $contract?->job_title ?? 'N/A' }}<br>
            Période: {{ $month }}/{{ $year }}
        </div>
    </div>

    <h1>FICHE DE PAIE PRO FORMA (ESTIMATION)</h1>

    <p style="text-align: center; color: #e11d48; margin-bottom: 20px;">
        <em>Document non officiel - À titre indicatif uniquement</em>
    </p>

    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Base (Heures/Jours)</th>
                <th class="right">Taux (€)</th>
                <th class="right">Montant Brut (€)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Heures Normales travaillées</td>
                <td class="right">{{ number_format($summary['total_hours'] - $summary['overtime_25'] - $summary['overtime_50'], 2) }}</td>
                <td class="right">{{ number_format($summary['hourly_rate'], 2) }}</td>
                <td class="right">{{ number_format(($summary['total_hours'] - $summary['overtime_25'] - $summary['overtime_50']) * $summary['hourly_rate'], 2) }}</td>
            </tr>
            @if($summary['overtime_25'] > 0)
            <tr>
                <td>Heures Supplémentaires (25%)</td>
                <td class="right">{{ number_format($summary['overtime_25'], 2) }}</td>
                <td class="right">{{ number_format($summary['hourly_rate'] * 1.25, 2) }}</td>
                <td class="right">{{ number_format($summary['overtime_25'] * $summary['hourly_rate'] * 1.25, 2) }}</td>
            </tr>
            @endif
            @if($summary['overtime_50'] > 0)
            <tr>
                <td>Heures Supplémentaires (50%)</td>
                <td class="right">{{ number_format($summary['overtime_50'], 2) }}</td>
                <td class="right">{{ number_format($summary['hourly_rate'] * 1.50, 2) }}</td>
                <td class="right">{{ number_format($summary['overtime_50'] * $summary['hourly_rate'] * 1.50, 2) }}</td>
            </tr>
            @endif
            @if($summary['gd_days'] > 0)
            <tr>
                <td>Indemnités Grand Déplacement (Forfait)</td>
                <td class="right">{{ $summary['gd_days'] }}</td>
                <td class="right">96.00</td>
                <td class="right">{{ number_format($summary['gd_allowance'], 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" class="right">TOTAL BRUT ESTIMÉ</td>
                <td class="right">{{ number_format($summary['gross_salary_estimate'], 2) }} €</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Généré le {{ $generated_at }} par le système RH Batistack.<br>
        Ce document est une estimation basée sur les pointages validés et ne remplace pas le bulletin de paie officiel.
    </div>
</body>
</html>
