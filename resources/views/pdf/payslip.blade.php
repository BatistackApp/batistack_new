<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Paie - {{ $employee->first_name }} {{ $employee->last_name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .container { width: 100%; padding: 20px; }
        .header { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header table { width: 100%; }
        .company-info { font-size: 12px; }
        .employee-info { font-size: 12px; text-align: right; }
        h1 { text-align: center; font-size: 16px; margin: 10px 0; }
        .period-info { text-align: center; font-size: 12px; margin-bottom: 20px; }
        
        .lines-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .lines-table th, .lines-table td { border: 1px solid #ddd; padding: 4px; text-align: right; }
        .lines-table th { background-color: #f5f5f5; text-align: center; font-size: 10px; }
        .lines-table td.text-left { text-align: left; }
        
        .category-row { background-color: #eaeaea; font-weight: bold; text-align: left; }
        
        .totals-table { width: 50%; float: right; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table th, .totals-table td { border: 1px solid #ddd; padding: 4px; text-align: right; }
        
        .footer { clear: both; margin-top: 40px; font-size: 10px; text-align: center; color: #555; }
        .net-pay { font-size: 16px; font-weight: bold; padding: 10px; border: 2px solid #000; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td class="company-info" style="width: 50%;">
                        <strong>BATISTACK BTP</strong><br>
                        123 Rue de la Construction<br>
                        75000 PARIS<br>
                        SIRET : 123 456 789 00012
                    </td>
                    <td class="employee-info" style="width: 50%;">
                        <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong><br>
                        {{ $employee->address }}<br>
                        {{ $employee->postal_code }} {{ $employee->city }}<br>
                        Emploi : {{ $employee->qualification ?? 'Ouvrier' }}<br>
                        Sécurité Sociale : {{ $employee->social_security_number }}
                    </td>
                </tr>
            </table>
        </div>

        <h1>BULLETIN DE PAIE</h1>
        <div class="period-info">
            Période du : 01/{{ substr($payslip->period, 5, 2) }}/{{ substr($payslip->period, 0, 4) }} 
            au {{ \Carbon\Carbon::parse($payslip->period . '-01')->endOfMonth()->format('d/m/Y') }}
        </div>

        <table class="lines-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 40%;">Rubriques</th>
                    <th rowspan="2">Base</th>
                    <th colspan="2">Part Salariale</th>
                    <th colspan="2">Part Patronale</th>
                </tr>
                <tr>
                    <th>Taux</th>
                    <th>Montant</th>
                    <th>Taux</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                <!-- Salaire de base -->
                <tr>
                    <td class="text-left">Salaire de Base</td>
                    <td>{{ number_format($payslip->base_hours, 2, ',', ' ') }}</td>
                    <td>{{ number_format($payslip->hourly_rate, 4, ',', ' ') }}</td>
                    <td>{{ number_format($payslip->gross_salary, 2, ',', ' ') }}</td>
                    <td></td>
                    <td></td>
                </tr>

                @foreach($lines as $category => $categoryLines)
                    <tr>
                        <td colspan="6" class="category-row text-left">{{ $category }}</td>
                    </tr>
                    @foreach($categoryLines as $line)
                        <tr>
                            <td class="text-left" style="padding-left: 15px;">{{ $line->label }}</td>
                            <td>{{ number_format($line->base, 2, ',', ' ') }}</td>
                            <td>{{ $line->employee_rate > 0 ? number_format($line->employee_rate, 4, ',', ' ') . '%' : '' }}</td>
                            <td>{{ $line->employee_amount > 0 ? number_format($line->employee_amount, 2, ',', ' ') : '' }}</td>
                            <td>{{ $line->employer_rate > 0 ? number_format($line->employer_rate, 4, ',', ' ') . '%' : '' }}</td>
                            <td>{{ $line->employer_amount > 0 ? number_format($line->employer_amount, 2, ',', ' ') : '' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        @if($advances->count() > 0)
        <table class="lines-table" style="width: 50%; float: left; margin-right: 20px;">
            <thead>
                <tr>
                    <th colspan="2">Acomptes déduits</th>
                </tr>
            </thead>
            <tbody>
                @foreach($advances as $advance)
                <tr>
                    <td class="text-left">Acompte du {{ $advance->payment_date->format('d/m/Y') }}</td>
                    <td>- {{ number_format($advance->amount, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table class="totals-table">
            <tr>
                <td class="text-left"><strong>Total Brut</strong></td>
                <td><strong>{{ number_format($payslip->gross_salary, 2, ',', ' ') }} €</strong></td>
            </tr>
            <tr>
                <td class="text-left">Net Social</td>
                <td>{{ number_format($payslip->net_social, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="text-left">Net Imposable</td>
                <td>{{ number_format($payslip->taxable_net, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="text-left">Impôt à la source ({{ number_format($payslip->pas_rate, 2, ',', ' ') }}%)</td>
                <td>- {{ number_format($payslip->pas_amount, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="text-left" style="background-color: #f5f5f5;"><strong>NET À PAYER</strong></td>
                <td style="background-color: #f5f5f5;">
                    <div class="net-pay">{{ number_format($payslip->net_paid, 2, ',', ' ') }} €</div>
                </td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <div class="footer">
            <p>Dans votre intérêt et pour vous aider à faire valoir vos droits, conservez ce bulletin de paie sans limitation de durée.</p>
            <p>Coût total employeur : {{ number_format($payslip->employer_cost, 2, ',', ' ') }} €</p>
        </div>
    </div>
</body>
</html>
