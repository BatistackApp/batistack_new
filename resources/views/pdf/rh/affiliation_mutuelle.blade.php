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
        th { background-color: #f9f9f9; font-weight: bold; width: 25%; }
        .signature-box { margin-top: 40px; border: 1px dashed #ccc; padding: 20px; text-align: right; height: 100px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">BULLETIN D'AFFILIATION - PRO BTP</div>
        <div class="subtitle">Prévoyance / Mutuelle Santé</div>
        <div class="subtitle">Date: {{ $generated_at }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. ENTREPRISE ADHÉRENTE</div>
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
        <div class="section-title">2. SALARIÉ(E)</div>
        <table>
            <tr>
                <th>Nom / Prénom</th>
                <td>{{ $employee->full_name }}</td>
                <th>Date de Naissance</th>
                <td>{{ $employee->birth_date ? $employee->birth_date->format('d/m/Y') : 'Non renseignée' }}</td>
            </tr>
            <tr>
                <th>N° Sécurité Sociale</th>
                <td>{{ $employee->social_security_number ?? 'Non renseigné' }}</td>
                <th>Matricule</th>
                <td>{{ $employee->registration_number }}</td>
            </tr>
            <tr>
                <th>Adresse Personnelle</th>
                <td colspan="3">{{ $employee->address }}, {{ $employee->postal_code }} {{ $employee->city }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $employee->email }}</td>
                <th>Téléphone</th>
                <td>{{ $employee->phone }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. EMPLOI ET CONTRAT</div>
        <table>
            <tr>
                <th>Poste / Qualification</th>
                <td>{{ $contract ? $contract->job_title : 'N/A' }}</td>
                <th>Type de Contrat</th>
                <td>{{ $contract ? $contract->type?->getLabel() : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Date d'embauche</th>
                <td>{{ $contract ? $contract->start_date->format('d/m/Y') : 'N/A' }}</td>
                <th>Heures hebdomadaires</th>
                <td>{{ $contract ? $contract->weekly_hours . ' h' : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section" style="background-color: #eef9ee; border: 1px solid #4caf50; padding: 10px; font-size: 11px;">
        <p><strong>DÉCLARATION :</strong> Par la présente, l'entreprise demande l'affiliation du salarié désigné ci-dessus aux régimes de retraite complémentaire, prévoyance et/ou santé gérés par PRO BTP selon les accords collectifs en vigueur dans la profession.</p>
    </div>

    <div class="signature-box">
        <p>Fait à {{ $company->city }}</p>
        <p>Le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        <br><br>
        <p><em>Signature de l'Employeur (Cachet)</em></p>
    </div>

</body>
</html>
