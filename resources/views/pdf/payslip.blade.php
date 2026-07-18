<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Paie - {{ $employee->first_name }} {{ $employee->last_name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-info {
            font-size: 10px;
            line-height: 1.3;
        }
        .employee-info {
            font-size: 11px;
            background-color: #e6eef5;
            padding: 10px;
            border-radius: 4px;
            line-height: 1.3;
        }
        .doc-title {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }
        .doc-period {
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        .emp-details-table {
            margin-bottom: 15px;
            font-size: 9px;
        }
        .emp-details-table td {
            padding: 2px 5px;
        }
        .lines-table {
            margin-bottom: 10px;
            border: 1px solid #000;
            border-bottom: none;
        }
        .lines-table th {
            background-color: #000080;
            color: #fff;
            padding: 5px;
            font-weight: bold;
            border-right: 1px solid #fff;
            text-align: center;
        }
        .lines-table th:last-child {
            border-right: none;
        }
        .lines-table td {
            padding: 3px 5px;
            border-right: 1px solid #000;
            vertical-align: top;
        }
        .lines-table td:last-child {
            border-right: none;
        }
        .col-elements { width: 40%; text-align: left; }
        .col-base { width: 10%; text-align: right; }
        .col-taux { width: 8%; text-align: right; }
        .col-deduire { width: 10%; text-align: right; }
        .col-payer { width: 10%; text-align: right; }
        .col-patronal { width: 22%; text-align: right; }

        .category-title {
            font-weight: bold;
            padding-top: 5px;
            padding-bottom: 2px;
        }
        .total-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
            padding: 5px;
        }
        
        .net-section {
            margin-top: 10px;
            width: 100%;
        }
        .net-section td {
            padding: 3px 5px;
        }
        .net-large {
            font-size: 14px;
            font-weight: bold;
        }
        
        .summary-table {
            margin-top: 20px;
            border: 1px solid #000;
        }
        .summary-table th {
            background-color: #000080;
            color: #fff;
            padding: 4px;
            text-align: center;
            border-right: 1px solid #fff;
        }
        .summary-table th:last-child { border-right: none; }
        .summary-table td {
            padding: 4px;
            text-align: right;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .summary-table td:last-child { border-right: none; }
        
        .final-net-box {
            margin-top: 10px;
            border: 1px solid #000;
        }
        .final-net-box td {
            padding: 5px;
            font-size: 12px;
        }
        .final-net-amount {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        .footer-text {
            text-align: center;
            font-size: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td style="width: 40%;" class="company-info">
                    <strong>BATISTACK BTP</strong><br>
                    123 Rue de la Construction<br>
                    75000 PARIS<br>
                    SIRET : 123 456 789 00012 Code Naf : 4120B<br>
                    Urssaf/Msa : 527256211335
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 40%;">
                    <div class="doc-title">BULLETIN DE SALAIRE</div>
                    <div class="doc-period">
                        Période : {{ \Carbon\Carbon::parse($payslip->period . '-01')->translatedFormat('F Y') }}
                    </div>
                    <br><br>
                    <div class="employee-info">
                        <strong>{{ $employee->title ?? 'Monsieur' }} {{ strtoupper($employee->last_name) }} {{ $employee->first_name }}</strong><br>
                        {{ $employee->address }}<br>
                        {{ $employee->postal_code }} {{ $employee->city }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- EMP DETAILS -->
        <table class="emp-details-table">
            <tr>
                <td style="width: 15%; text-align: right;">Matricule :</td>
                <td style="width: 35%;"><strong>{{ str_pad($employee->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                <td style="width: 15%; text-align: right;">Entrée :</td>
                <td style="width: 35%;"><strong>{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('d/m/Y') : '' }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">N° SS :</td>
                <td><strong>{{ $employee->social_security_number }}</strong></td>
                <td style="text-align: right;">Ancienneté :</td>
                <td><strong>{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->diffInMonths(now()) . ' mois' : '' }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">Emploi :</td>
                <td><strong>{{ $employee->qualification ?? 'Ouvrier' }}</strong></td>
                <td style="text-align: right;">Convention collective :</td>
                <td><strong>Bâtiment (ETAM)</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">Statut professionnel :</td>
                <td><strong>Employé</strong></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="text-align: right;">Niveau :</td>
                <td><strong>B</strong></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <!-- MAIN TABLE -->
        @php
            $totalDeduire = 0;
            $totalEmployer = 0;
            $totalExonerations = 939.74; // Valeur d'exemple, on la soustrait du total employeur plus bas
            
            // On calcule le total des retenues pour l'affichage
            foreach($lines as $category => $categoryLines) {
                foreach($categoryLines as $line) {
                    $totalDeduire += $line->employee_amount;
                    $totalEmployer += $line->employer_amount;
                }
            }
        @endphp

        <table class="lines-table">
            <thead>
                <tr>
                    <th class="col-elements">Eléments de paie</th>
                    <th class="col-base">Base</th>
                    <th class="col-taux">Taux</th>
                    <th class="col-deduire">A déduire</th>
                    <th class="col-payer">A payer</th>
                    <th class="col-patronal">Charges patronales</th>
                </tr>
            </thead>
            <tbody>
                <!-- Base -->
                <tr>
                    <td class="col-elements">Salaire de base</td>
                    <td class="col-base">{{ number_format($payslip->base_hours, 2, '.', ' ') }}</td>
                    <td class="col-taux">{{ number_format($payslip->hourly_rate, 4, '.', ' ') }}</td>
                    <td class="col-deduire"></td>
                    <td class="col-payer">{{ number_format($payslip->gross_salary, 2, '.', ' ') }}</td>
                    <td class="col-patronal"></td>
                </tr>
                <tr>
                    <td class="col-elements" style="font-weight: bold; padding-bottom: 10px;">Salaire brut</td>
                    <td class="col-base"></td>
                    <td class="col-taux"></td>
                    <td class="col-deduire"></td>
                    <td class="col-payer" style="font-weight: bold;">{{ number_format($payslip->gross_salary, 2, '.', ' ') }}</td>
                    <td class="col-patronal" style="border-bottom: 1px solid #ccc;"></td>
                </tr>

                <!-- Cotisations -->
                @foreach($lines as $category => $categoryLines)
                    <tr>
                        <td class="col-elements category-title">{{ $category }}</td>
                        <td class="col-base"></td><td class="col-taux"></td><td class="col-deduire"></td><td class="col-payer"></td><td class="col-patronal"></td>
                    </tr>
                    @foreach($categoryLines as $line)
                        <tr>
                            <td class="col-elements">{{ $line->label }}</td>
                            <td class="col-base">{{ $line->base > 0 ? number_format($line->base, 2, '.', ' ') : '' }}</td>
                            
                            <!-- Part Salariale -->
                            @if($line->employee_rate > 0)
                                <td class="col-taux">{{ number_format($line->employee_rate, 4, '.', ' ') }}</td>
                                <td class="col-deduire">{{ number_format($line->employee_amount, 2, '.', ' ') }}</td>
                            @else
                                <td class="col-taux"></td><td class="col-deduire"></td>
                            @endif
                            
                            <td class="col-payer"></td>
                            
                            <!-- Part Patronale -->
                            <td class="col-patronal">
                                @if($line->employer_rate > 0)
                                    <span style="float:left">{{ number_format($line->base, 2, '.', ' ') }}</span>
                                    <span style="margin-left:10px">{{ number_format($line->employer_rate, 4, '.', ' ') }}</span>
                                    <span style="float:right">{{ number_format($line->employer_amount, 2, '.', ' ') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                
                <tr>
                    <td class="col-elements category-title">Exonérations de cotisations employeur</td>
                    <td class="col-base"></td><td class="col-taux"></td><td class="col-deduire"></td><td class="col-payer"></td>
                    <td class="col-patronal" style="text-align: right;">- {{ number_format($totalExonerations, 2, '.', ' ') }}</td>
                </tr>

                <tr style="height: 20px;"><td colspan="6"></td></tr>

                <tr class="total-row">
                    <td class="col-elements">Total des cotisations et contributions</td>
                    <td class="col-base"></td>
                    <td class="col-taux"></td>
                    <td class="col-deduire">{{ number_format($totalDeduire, 2, '.', ' ') }}</td>
                    <td class="col-payer"></td>
                    <td class="col-patronal">{{ number_format($totalEmployer - $totalExonerations, 2, '.', ' ') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- NET SECTION -->
        <table class="net-section">
            <tr>
                <td style="width: 40%;">Réintégration fiscale</td>
                <td style="width: 10%;"></td>
                <td style="width: 25%; text-align: right;">57.59</td>
                <td style="width: 25%;"></td>
            </tr>
            <tr>
                <td>Montant net social</td>
                <td></td>
                <td style="text-align: right;">{{ number_format($payslip->net_social, 2, '.', ' ') }}</td>
                <td></td>
            </tr>
            <tr><td colspan="4" style="height: 5px;"></td></tr>
            <tr>
                <td class="net-large">Net à payer avant impôt sur le revenu</td>
                <td></td>
                <td></td>
                <td class="net-large" style="text-align: right;">{{ number_format($payslip->net_payable + $payslip->pas_amount, 2, '.', ' ') }}</td>
            </tr>
            <tr>
                <td>Impôt sur le revenu prélevé à la source - PAS</td>
                <td style="text-align: right;">{{ number_format($payslip->taxable_net, 2, '.', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($payslip->pas_rate, 4, '.', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($payslip->pas_amount, 2, '.', ' ') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Taux personnalisé</td>
                <td colspan="3"></td>
            </tr>
            <tr><td colspan="4" style="height: 5px;"></td></tr>
            <tr>
                <td style="font-weight: bold;">Net payé</td>
                <td></td>
                <td></td>
                <td style="font-weight: bold; text-align: right;">{{ number_format($payslip->net_paid, 2, '.', ' ') }}</td>
            </tr>
            
            @if($advances->count() > 0)
                @foreach($advances as $advance)
                <tr>
                    <td>Acompte du {{ $advance->payment_date ? $advance->payment_date->format('d/m/Y') : '' }}</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">- {{ number_format($advance->amount, 2, '.', ' ') }}</td>
                </tr>
                @endforeach
            @endif
        </table>

        <!-- SUMMARY TABLES -->
        <div style="font-weight: bold; margin-bottom: 5px; font-size: 10px;">Cumuls Mensuels</div>
        <table class="summary-table" style="margin-top: 0; margin-bottom: 15px;">
            <thead>
                <tr>
                    <th style="width: 11%;">Heures</th>
                    <th style="width: 11%;">Heures suppl.</th>
                    <th style="width: 11%;">Brut</th>
                    <th style="width: 11%;">Plafond S.S.</th>
                    <th style="width: 11%;">Net imposable</th>
                    <th style="width: 11%;">Ch. patronales</th>
                    <th style="width: 11%;">Coût Global</th>
                    <th style="width: 11%;">Total versé</th>
                    <th style="width: 12%;">Allègements</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($payslip->base_hours, 2, '.', ' ') }}</td>
                    <td></td>
                    <td>{{ number_format($payslip->gross_salary, 2, '.', ' ') }}</td>
                    <td>4005.00</td>
                    <td>{{ number_format($payslip->taxable_net, 2, '.', ' ') }}</td>
                    <td>{{ number_format($totalEmployer - $totalExonerations, 2, '.', ' ') }}</td>
                    <td>{{ number_format($payslip->employer_cost, 2, '.', ' ') }}</td>
                    <td>{{ number_format($payslip->employer_cost + $payslip->pas_amount, 2, '.', ' ') }}</td>
                    <td>{{ number_format($totalExonerations, 2, '.', ' ') }}</td>
                </tr>
            </tbody>
        </table>

        <div style="font-weight: bold; margin-bottom: 5px; font-size: 10px;">Cumuls Annuels</div>
        <table class="summary-table" style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 11%;">Heures</th>
                    <th style="width: 11%;">Heures suppl.</th>
                    <th style="width: 11%;">Brut</th>
                    <th style="width: 11%;">Plafond S.S.</th>
                    <th style="width: 11%;">Net imposable</th>
                    <th style="width: 11%;">Ch. patronales</th>
                    <th style="width: 11%;">Coût Global</th>
                    <th style="width: 11%;">Total versé</th>
                    <th style="width: 12%;">Allègements</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($payslip->base_hours * 5, 2, '.', ' ') }}</td>
                    <td></td>
                    <td>{{ number_format($payslip->gross_salary * 5, 2, '.', ' ') }}</td>
                    <td>20025.00</td>
                    <td>{{ number_format($payslip->taxable_net * 5, 2, '.', ' ') }}</td>
                    <td>{{ number_format(($totalEmployer - $totalExonerations) * 5, 2, '.', ' ') }}</td>
                    <td>{{ number_format($payslip->employer_cost * 5, 2, '.', ' ') }}</td>
                    <td>{{ number_format(($payslip->employer_cost + $payslip->pas_amount) * 5, 2, '.', ' ') }}</td>
                    <td>{{ number_format($totalExonerations * 5, 2, '.', ' ') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- FINAL NET BOX -->
        <table class="final-net-box">
            <tr>
                <td style="width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Acquis</td>
                <td style="width: 80%; border-bottom: 1px solid #000;" rowspan="2" class="final-net-amount">
                    Net payé : {{ number_format($payslip->net_paid, 2, '.', ' ') }} euros
                </td>
            </tr>
            <tr>
                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">Pris</td>
            </tr>
            <tr>
                <td style="border-right: 1px solid #000;">Solde</td>
                <td style="text-align: right;">
                    Paiement le {{ $payslip->payment_date ? \Carbon\Carbon::parse($payslip->payment_date)->format('d/m/Y') : \Carbon\Carbon::parse($payslip->period . '-01')->endOfMonth()->format('d/m/Y') }} par Virement
                </td>
            </tr>
        </table>

        <div class="footer-text">
            Dans votre intérêt, et pour vous aider à faire valoir vos droits, conservez ce bulletin de paie sans limitation de durée. Informations complémentaires : www.service-public.fr
        </div>
    </div>
</body>
</html>
