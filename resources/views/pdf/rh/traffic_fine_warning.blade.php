<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-width: 200px; max-height: 80px; margin-bottom: 10px; }
        .company-info { font-size: 12px; color: #64748b; }
        h1 { font-size: 20px; color: #0f172a; margin: 0 0 10px 0; }
        .meta-info { margin-bottom: 30px; font-size: 13px; }
        .meta-info p { margin: 2px 0; }
        .content { margin-bottom: 40px; }
        .fine-details { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .fine-details h2 { margin-top: 0; font-size: 16px; color: #1e293b; }
        .fine-details table { width: 100%; border-collapse: collapse; }
        .fine-details th, .fine-details td { padding: 8px 0; text-align: left; }
        .fine-details th { width: 40%; color: #64748b; font-weight: normal; }
        .footer { margin-top: 50px; font-size: 12px; color: #64748b; text-align: center; }
        .signature-box { margin-top: 40px; width: 300px; float: right; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 10px; }
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

    <div class="meta-info">
        <p><strong>Fait à :</strong> {{ $company ? $company->city : 'Siège' }}</p>
        <p><strong>Le :</strong> {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y') }}</p>
        <p><strong>Destinataire :</strong> {{ $employee->full_name }}</p>
        <p><strong>Matricule :</strong> {{ $employee->registration_number }}</p>
    </div>

    <h1>Objet : Avertissement - Infraction au Code de la Route</h1>

    <div class="content">
        <p>Monsieur / Madame {{ $employee->last_name }},</p>
        
        <p>Nous vous informons par la présente que notre société a été destinataire d'un avis de contravention concernant le véhicule <strong>{{ $fine->vehicle->license_plate }}</strong> ({{ $fine->vehicle->brand }} {{ $fine->vehicle->model }}).</p>
        
        <p>Selon nos registres, vous étiez le conducteur assigné à ce véhicule au moment de l'infraction.</p>

        <div class="fine-details">
            <h2>Détails de l'infraction</h2>
            <table>
                <tr>
                    <th>Référence de l'avis :</th>
                    <td>{{ $fine->reference }}</td>
                </tr>
                <tr>
                    <th>Date de l'infraction :</th>
                    <td>{{ $fine->infraction_at->format('d/m/Y à H:i') }}</td>
                </tr>
                <tr>
                    <th>Véhicule concerné :</th>
                    <td>{{ $fine->vehicle->license_plate }}</td>
                </tr>
                <tr>
                    <th>Montant de l'amende :</th>
                    <td>{{ number_format($fine->amount, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <th>Retrait de points :</th>
                    <td>{{ $fine->points_deducted > 0 ? $fine->points_deducted . ' point(s)' : 'Aucun' }}</td>
                </tr>
            </table>
        </div>

        <p>Conformément à la législation en vigueur et au règlement intérieur de l'entreprise, nous sommes dans l'obligation de procéder à la désignation du conducteur auprès de l'Agence Nationale de Traitement Automatisé des Infractions (ANTAI).</p>
        
        <p>Nous vous rappelons que le respect du Code de la Route est une obligation impérative dans le cadre de vos fonctions. Les frais liés à cette infraction, ainsi que les éventuelles pertes de points, seront à votre charge.</p>

        <p>Nous vous prions d'agréer, Monsieur / Madame {{ $employee->last_name }}, l'expression de nos salutations distinguées.</p>

        <div class="signature-box">
            <p><strong>La Direction des Ressources Humaines</strong></p>
            <div class="signature-line">Signature</div>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        Document généré automatiquement par Batistack le {{ $generated_at }}
    </div>
</body>
</html>
