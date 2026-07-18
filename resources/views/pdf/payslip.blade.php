<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Paie - {{ $employee->first_name }} {{ $employee->last_name }}</title>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            margin: 20px;
            padding: 0;
            color: #334155; /* slate-700 */
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            display: table-header-group;
        }
        .lines-table tr, .summary-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
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
            background-color: #f1f5f9; /* slate-100 */
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0; /* slate-200 */
            line-height: 1.4;
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
            border: 1px solid #cbd5e1; /* slate-300 */
            border-bottom: 1px solid #cbd5e1; /* Fixes unclosed bottom border */
        }
        .lines-table th {
            background-color: #1e293b; /* slate-800 */
            color: #f8fafc; /* slate-50 */
            padding: 6px 5px;
            font-weight: 600;
            border-right: 1px solid #475569; /* slate-600 */
            text-align: center;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.05em;
        }
        .lines-table th:last-child {
            border-right: none;
        }
        .lines-table td {
            padding: 4px 5px;
            border-right: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .lines-table td:last-child {
            border-right: none;
        }
        .col-elements { width: 38%; text-align: left; }
        .col-base { width: 9%; text-align: right; padding-right: 5px; }
        .col-taux { width: 7%; text-align: right; padding-right: 5px; }
        .col-deduire { width: 9%; text-align: right; padding-right: 5px; }
        .col-payer { width: 9%; text-align: right; padding-right: 5px; }
        .col-pat-base { width: 10%; text-align: center; border-right: none !important; }
        .col-pat-taux { width: 9%; text-align: center; border-right: none !important; }
        .col-pat-mont { width: 9%; text-align: right; padding-right: 5px; }

        .category-title {
            font-weight: bold;
            padding-top: 5px;
            padding-bottom: 2px;
        }
        .total-row td {
            border-top: 1px solid #94a3b8; /* slate-400 */
            border-bottom: 1px solid #94a3b8;
            background-color: #f8fafc;
            font-weight: 600;
            padding: 6px 5px;
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
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .summary-table tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-table tr:last-child {
            border-bottom: none;
        }
        .summary-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 500;
            padding: 8px 12px;
            border-right: 1px solid #f1f5f9;
            text-align: left;
        }
        .summary-table td {
            padding: 8px 12px;
            color: #334155;
            font-weight: 600;
            text-align: right;
        }

        .footer-text {
            text-align: center;
            font-size: 9px;
            color: #64748b; /* slate-500 */
            padding-top: 20px;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <table style="width: 100%; border: none; padding: 0; margin: 0;">
        <thead>
            <tr>
                <td style="border: none; padding: 0;">
        <!-- EMP DETAILS -->
        @php
            $company = \App\Models\Core\Company::first();
            $contract = $employee->currentContract;
            $periodEnd = \Carbon\Carbon::parse($payslip->period . '-01')->endOfMonth();
        @endphp
        <table class="header-table">
            <tr>
                <td style="width: 40%;" class="company-info">
                    <strong>{{ $company->legal_name ?? 'BATISTACK BTP' }}</strong><br>
                    {{ $company->address ?? '123 Rue de la Construction' }}<br>
                    {{ $company->zip_code ?? '75000' }} {{ $company->city ?? 'PARIS' }}<br>
                    SIRET : {{ $company->siret ?? '123 456 789 00012' }} <br>
                    Urssaf/Msa : {{ $company->urssaf ?? '527256211335' }}
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 40%;">
                    <div class="doc-title text-slate-800 text-lg tracking-tight">BULLETIN DE SALAIRE</div>
                    <div class="doc-period text-slate-500">
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
                <td style="width: 35%;"><strong>{{ $contract && $contract->start_date ? $contract->start_date->format('d/m/Y') : '' }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">N° SS :</td>
                <td><strong>{{ $employee->social_security_number }}</strong></td>
                <td style="text-align: right;">Ancienneté :</td>
                <td><strong>{{ $contract && $contract->start_date ? $contract->start_date->diffInMonths($periodEnd) . ' mois' : '' }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">Emploi :</td>
                <td><strong>{{ $contract ? $contract->job_title : ($employee->qualification ?? '') }}</strong></td>
                <td style="text-align: right;">Convention collective :</td>
                <td><strong>{{ $contract && $contract->payrollContributionProfile ? $contract->payrollContributionProfile->name : 'Bâtiment (ETAM)' }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: right;">Statut professionnel :</td>
                <td><strong>Employé</strong></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="text-align: right;">Niveau :</td>
                <td><strong>{{ $contract->level ?? $employee->level ?? 'B' }}</strong></td>
                <td></td>
                <td></td>
                    </table>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none; padding: 0;">
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
                    <th colspan="3">Charges patronales</th>
                </tr>
            </thead>
            <tbody>
                <!-- Base -->
                <tr>
                    <td class="col-elements">Salaire de base</td>
                    <td class="col-base">{{ number_format($payslip->base_hours, 2, '.', ' ') }}</td>
                    <td class="col-taux">{{ number_format($payslip->hourly_rate, 4, '.', ' ') }}</td>
                    <td class="col-deduire"></td>
                    <td class="col-payer">{{ number_format($payslip->base_hours * $payslip->hourly_rate, 2, '.', ' ') }}</td>
                    <td colspan="3"></td>
                </tr>
                @if($payslip->overtime_hours > 0)
                <tr>
                    <td class="col-elements">Heures supplémentaires majorées</td>
                    <td class="col-base">{{ number_format($payslip->overtime_hours, 2, '.', ' ') }}</td>
                    <td class="col-taux">{{ number_format($payslip->hourly_rate * 1.25, 4, '.', ' ') }}</td>
                    <td class="col-deduire"></td>
                    <td class="col-payer">{{ number_format($payslip->overtime_amount, 2, '.', ' ') }}</td>
                    <td colspan="3"></td>
                </tr>
                @endif
                @if(is_array($payslip->custom_bonuses))
                    @foreach($payslip->custom_bonuses as $bonus)
                        @if(!empty($bonus['is_taxable']) && $bonus['is_taxable'])
                        <tr>
                            <td class="col-elements">{{ $bonus['label'] }}</td>
                            <td class="col-base"></td>
                            <td class="col-taux"></td>
                            <td class="col-deduire"></td>
                            <td class="col-payer">{{ number_format((float)$bonus['amount'], 2, '.', ' ') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        @endif
                    @endforeach
                @endif
                <tr>
                    <td class="col-elements" style="font-weight: bold; padding-bottom: 10px;">Salaire brut</td>
                    <td class="col-base"></td>
                    <td class="col-taux"></td>
                    <td class="col-deduire"></td>
                    <td class="col-payer" style="font-weight: bold;">{{ number_format($payslip->gross_salary, 2, '.', ' ') }}</td>
                    <td colspan="3" style="border-bottom: 1px solid #ccc;"></td>
                </tr>

                <!-- Cotisations -->
                @foreach($lines as $category => $categoryLines)
                    <tr>
                        <td class="col-elements category-title">{{ $category }}</td>
                        <td class="col-base"></td><td class="col-taux"></td><td class="col-deduire"></td><td class="col-payer"></td><td colspan="3"></td>
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
                            @if($line->employer_rate > 0)
                                <td class="col-pat-base">{{ number_format($line->base, 2, '.', ' ') }}</td>
                                <td class="col-pat-taux">{{ number_format($line->employer_rate, 4, '.', ' ') }}</td>
                                <td class="col-pat-mont">{{ number_format($line->employer_amount, 2, '.', ' ') }}</td>
                            @else
                                <td class="col-pat-base"></td><td class="col-pat-taux"></td><td class="col-pat-mont"></td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach

                <tr>
                    <td class="col-elements category-title">Exonérations de cotisations employeur</td>
                    <td class="col-base"></td><td class="col-taux"></td><td class="col-deduire"></td><td class="col-payer"></td>
                    <td colspan="2" class="col-pat-base"></td>
                    <td class="col-pat-mont">- {{ number_format($totalExonerations, 2, '.', ' ') }}</td>
                </tr>

                <tr style="height: 20px;"><td colspan="8"></td></tr>

                <tr class="total-row">
                    <td class="col-elements">Total des cotisations et contributions</td>
                    <td class="col-base"></td><td class="col-taux"></td>
                    <td class="col-deduire">{{ number_format($totalDeduire, 2, '.', ' ') }}</td>
                    <td class="col-payer"></td>
                    <td colspan="2" class="col-pat-base"></td>
                    <td class="col-pat-mont">{{ number_format($totalEmployer - $totalExonerations, 2, '.', ' ') }}</td>
                </tr>

                <tr style="height: 10px;"><td colspan="8"></td></tr>

                <tr>
                    <td class="col-elements">Réintégration fiscale</td>
                    <td colspan="3"></td>
                    <td class="col-payer">57.59</td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td class="col-elements">Montant net social</td>
                    <td colspan="3"></td>
                    <td class="col-payer">{{ number_format($payslip->net_social, 2, '.', ' ') }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>

        <!-- NET PAYABLE SECTION -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;" class="border border-slate-300 rounded-lg shadow-sm bg-white">
            <tbody>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <td style="width: 40%; font-size: 13px; font-weight: bold; padding: 8px 12px; color: #0f172a;">Net à payer avant impôt sur le revenu</td>
                    <td style="width: 15%; padding: 8px 12px;"></td>
                    <td style="width: 20%; padding: 8px 12px;"></td>
                    <td style="width: 25%; font-size: 13px; font-weight: bold; text-align: right; padding: 8px 12px; color: #0f172a;">{{ number_format($payslip->net_payable + $payslip->pas_amount, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 12px; color: #475569;">Impôt sur le revenu prélevé à la source - PAS</td>
                    <td style="text-align: right; padding: 6px 12px; color: #475569;">{{ number_format($payslip->taxable_net, 2, '.', ' ') }}</td>
                    <td style="text-align: right; padding: 6px 12px; color: #475569;">{{ number_format($payslip->pas_rate, 4, '.', ' ') }}</td>
                    <td style="text-align: right; padding: 6px 12px; color: #475569;">{{ number_format($payslip->pas_amount, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 24px; font-style: italic; padding-bottom: 8px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Taux personnalisé</td>
                    <td colspan="3" style="border-bottom: 1px solid #e2e8f0;"></td>
                </tr>
            <tr><td colspan="4" style="height: 10px;"></td></tr>

            @if($payslip->gd_allowance_amount > 0)
            <tr>
                <td style="padding: 5px;">Indemnités de Grand Déplacement (non soumises)</td>
                <td colspan="2" style="padding: 5px;"></td>
                <td style="text-align: right; padding: 5px;">+ {{ number_format($payslip->gd_allowance_amount, 2, '.', ' ') }}</td>
            </tr>
            @endif

            @if($payslip->expense_reports_amount > 0)
            <tr>
                <td style="padding: 5px;">Remboursements Frais professionnels</td>
                <td colspan="2" style="padding: 5px;"></td>
                <td style="text-align: right; padding: 5px;">+ {{ number_format($payslip->expense_reports_amount, 2, '.', ' ') }}</td>
            </tr>
            @endif

            @if($payslip->meal_allowance_amount > 0)
            <tr>
                <td style="padding: 5px;">Indemnités de Repas (Paniers non soumis)</td>
                <td colspan="2" style="padding: 5px;"></td>
                <td style="text-align: right; padding: 5px;">+ {{ number_format($payslip->meal_allowance_amount, 2, '.', ' ') }}</td>
            </tr>
            @endif

            @php
                $nonTaxableBonusesAmount = 0;
            @endphp
            @if(is_array($payslip->custom_bonuses))
                @foreach($payslip->custom_bonuses as $bonus)
                    @if(empty($bonus['is_taxable']) || !$bonus['is_taxable'])
                    @php $nonTaxableBonusesAmount += (float)$bonus['amount']; @endphp
                    <tr>
                        <td style="padding: 5px;">{{ $bonus['label'] }} (Non soumis)</td>
                        <td colspan="2" style="padding: 5px;"></td>
                        <td style="text-align: right; padding: 5px;">+ {{ number_format((float)$bonus['amount'], 2, '.', ' ') }}</td>
                    </tr>
                    @endif
                @endforeach
            @endif

            @php
                $totalDu = $payslip->net_payable + $payslip->gd_allowance_amount + $payslip->expense_reports_amount + $payslip->meal_allowance_amount + $nonTaxableBonusesAmount;
                $advancesTotal = $advances->sum('amount');
            @endphp

            <tr>
                <td style="font-weight: 600; padding: 8px 12px; border-top: 1px solid #e2e8f0; color: #1e293b;">Total dû</td>
                <td colspan="2" style="border-top: 1px solid #e2e8f0; padding: 8px 12px;"></td>
                <td style="font-weight: 600; text-align: right; border-top: 1px solid #e2e8f0; padding: 8px 12px; color: #1e293b;">{{ number_format($totalDu, 2, '.', ' ') }}</td>
            </tr>

            @if($advances->count() > 0)
                @foreach($advances as $advance)
                <tr>
                    <td style="padding: 6px 12px; color: #ef4444;">Acompte du {{ $advance->payment_date ? $advance->payment_date->format('d/m/Y') : '' }}</td>
                    <td colspan="2" style="padding: 6px 12px;"></td>
                    <td style="text-align: right; padding: 6px 12px; color: #ef4444;">- {{ number_format($advance->amount, 2, '.', ' ') }}</td>
                </tr>
                @endforeach
            @endif
            
            <tr class="bg-blue-50">
                <td style="font-size: 16px; font-weight: 700; padding: 12px; border-top: 2px solid #3b82f6; color: #1e3a8a; border-bottom-left-radius: 8px;">Net payé</td>
                <td colspan="2" style="border-top: 2px solid #3b82f6; padding: 12px;"></td>
                <td style="font-size: 16px; font-weight: 700; text-align: right; padding: 12px; border-top: 2px solid #3b82f6; color: #1e3a8a; border-bottom-right-radius: 8px;">{{ number_format($payslip->net_paid, 2, '.', ' ') }}</td>
            </tr>
        </tbody>
        </table>

        <!-- SUMMARY TABLES SIDE-BY-SIDE -->
        <table style="width: 100%; border: none; margin-bottom: 15px;">
            <tr>
                <td style="width: 48%; vertical-align: top;">
                    <div style="font-weight: bold; margin-bottom: 5px; font-size: 10px;">Cumuls Mensuels</div>
                    <table class="summary-table" style="margin-top: 0; width: 100%;">
            <tbody>
                <tr>
                    <th style="width: 50%; text-align: left; padding-left: 10px;">Heures</th>
                    <td style="width: 50%; text-align: right; padding-right: 10px;">{{ number_format($payslip->base_hours, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Heures suppl.</th>
                    <td style="text-align: right; padding-right: 10px;">{{ $payslip->overtime_hours > 0 ? number_format($payslip->overtime_hours, 2, '.', ' ') : '' }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Brut</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($payslip->gross_salary, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Plafond S.S.</th>
                    <td style="text-align: right; padding-right: 10px;">4 005.00</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Net imposable</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($payslip->taxable_net, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Ch. patronales</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($totalEmployer - $totalExonerations, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Coût Global</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($payslip->employer_cost, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Total versé</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($payslip->employer_cost + $payslip->pas_amount, 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Allègements</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($totalExonerations, 2, '.', ' ') }}</td>
                </tr>
            </tbody>
                    </table>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top;">
                    <div style="font-weight: bold; margin-bottom: 5px; font-size: 10px;">Cumuls Annuels</div>
                    <table class="summary-table" style="margin-top: 0; width: 100%;">
            <tbody>
                <tr>
                    <th style="width: 50%; text-align: left; padding-left: 10px;">Heures</th>
                    <td style="width: 50%; text-align: right; padding-right: 10px;">{{ number_format($annualTotals['base_hours'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Heures suppl.</th>
                    <td style="text-align: right; padding-right: 10px;">{{ $annualTotals['overtime_hours'] > 0 ? number_format($annualTotals['overtime_hours'], 2, '.', ' ') : '' }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Brut</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['gross_salary'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Plafond S.S.</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format(4005.00 * (intval(substr($payslip->period, 5, 2))), 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Net imposable</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['taxable_net'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Ch. patronales</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['employer_contributions'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Coût Global</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['employer_cost'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Total versé</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['employer_cost'] + $annualTotals['pas_amount'], 2, '.', ' ') }}</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 10px;">Allègements</th>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($annualTotals['exonerations'], 2, '.', ' ') }}</td>
                </tr>
            </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- FINAL NET BOX -->
        <table style="width: 100%; border-collapse: collapse; margin-top: 24px;" class="border border-slate-300 rounded-lg shadow-sm bg-white overflow-hidden">
            <tr>
                <td style="width: 20%; padding: 12px; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 500;">Acquis</td>
                <td style="width: 80%; padding: 16px; border-bottom: 1px solid #e2e8f0; font-size: 16px; font-weight: bold; text-align: center; color: #1e3a8a; background-color: #f8fafc;" rowspan="2">
                    Net payé : {{ number_format($payslip->net_paid, 2, '.', ' ') }} euros
                </td>
            </tr>
            <tr>
                <td style="padding: 12px; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 500;">Pris</td>
            </tr>
            <tr>
                <td style="padding: 12px; border-right: 1px solid #e2e8f0; color: #475569; font-weight: 500;">Solde</td>
                <td style="padding: 12px; text-align: right; color: #64748b; font-size: 11px;">
                    Paiement le {{ $payslip->payment_date ? \Carbon\Carbon::parse($payslip->payment_date)->format('d/m/Y') : \Carbon\Carbon::parse($payslip->period . '-01')->endOfMonth()->format('d/m/Y') }} par Virement
                </td>
            </tr>
        </table>
        
        <div style="height: 50px;"></div> <!-- Spacer for footer -->
        
        </tbody>
        <tfoot>
            <tr>
                <td style="border: none; padding: 0;">
                    <div class="footer-text">
                        Dans votre intérêt, et pour vous aider à faire valoir vos droits, conservez ce bulletin de paie sans limitation de durée. Informations complémentaires : www.service-public.fr
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
