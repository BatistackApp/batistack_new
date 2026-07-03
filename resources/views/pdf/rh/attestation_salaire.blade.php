<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #0056b3; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; background-color: #f4f4f4; padding: 5px; border-left: 4px solid #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; }
        .footer { margin-top: 50px; font-size: 10px; color: #999; text-align: center; }
        .signature-box { margin-top: 30px; border: 1px dashed #ccc; padding: 20px; text-align: right; height: 80px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">ATTESTATION DE SALAIRE</div>
        <div class="subtitle">Pour le versement des indemnités journalières (Maladie / Accident)</div>
        <div class="subtitle">Date: {{ $generated_at }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. EMPLOYEUR</div>
        <table>
            <tr>
                <th>Raison Sociale</th>
                <td>{{ $company->legal_name }}</td>
                <th>SIRET</th>
                <td>{{ $company->siret }}</td>
            </tr>
            <tr>
                <th>Adresse</th>
                <td colspan="3">{{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. ASSURÉ(E)</div>
        <table>
            <tr>
                <th>Nom / Prénom</th>
                <td>{{ $employee->full_name }}</td>
                <th>N° Sécurité Sociale</th>
                <td>{{ $employee->social_security_number ?? 'Non renseigné' }}</td>
            </tr>
            <tr>
                <th>Emploi</th>
                <td>{{ $employee->currentContract ? $employee->currentContract->job_title : 'N/A' }}</td>
                <th>Date d'embauche</th>
                <td>{{ $employee->currentContract ? $employee->currentContract->start_date->format('d/m/Y') : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. RENSEIGNEMENTS POUR L'ÉTUDE DES DROITS</div>
        <table>
            <tr>
                <th>Dernier jour de travail</th>
                <td>{{ $absence->start_date->subDay()->format('d/m/Y') }}</td>
                <th>Motif de l'arrêt</th>
                <td>{{ $absence->getType()->getLabel() }}</td>
            </tr>
            <tr>
                <th>Date de reprise prévue</th>
                <td>{{ $absence->end_date->addDay()->format('d/m/Y') }}</td>
                <th>Subrogation</th>
                <td>{{ $absence->requires_subrogation ? 'OUI (Maintien de salaire par l\'employeur)' : 'NON' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. SALAIRES DE RÉFÉRENCE (3 derniers mois)</div>
        <table>
            <thead>
                <tr>
                    <th>Période de paie</th>
                    <th>Nb Heures travaillées</th>
                    <th>Salaire Brut Soumis à Cotisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reference_salaries as $salary)
                <tr>
                    <td>{{ $salary['period'] }}</td>
                    <td>{{ $salary['hours'] }} h</td>
                    <td>{{ number_format($salary['amount'], 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size: 11px; font-style: italic;">Note: Ces montants sont indicatifs et basés sur le contrat actuel pour l'estimation PRO BTP.</p>
    </div>

    @if($absence->requires_subrogation)
    <div class="section" style="background-color: #eef9ee; border: 1px solid #4caf50; padding: 10px;">
        <h4 style="color: #4caf50; margin-top: 0;">Demande de Subrogation</h4>
        <p>L'entreprise demande la subrogation et le versement des indemnités journalières sur le compte de l'entreprise dont le RIB a été fourni préalablement à la caisse.</p>
        <p><strong>Période demandée :</strong> du {{ $absence->start_date->format('d/m/Y') }} au {{ $absence->end_date->format('d/m/Y') }}</p>
    </div>
    @endif

    <div class="signature-box">
        <p>Fait pour valoir ce que de droit, à {{ $company->city }}</p>
        <p>Le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        <br>
        <p><em>Signature de l'Employeur</em></p>
    </div>

</body>
</html>
