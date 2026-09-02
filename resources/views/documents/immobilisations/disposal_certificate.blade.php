<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV de Cession / Mise au Rebut</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; font-size: 10pt; line-height: 1.3; color: #1f2937; }
    </style>
</head>
<body>

    <div style="text-align:center; padding-bottom:8px; border-bottom:2px solid #ef4444; margin-bottom:12px;">
        <h1 style="font-size:16pt; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; color:#111827;">Procès-Verbal de Sortie d'Actif</h1>
        <p style="color:#dc2626; margin-top:2px; font-size:10pt; font-weight:600;">Attestation de Cession / Mise au Rebut</p>
    </div>

    <p style="margin-bottom:10px; text-align:justify; color:#374151;">
        Je soussigné, représentant légal de l'entreprise, certifie par la présente que l'immobilisation désignée ci-dessous a été sortie de notre parc actif en date du <strong>{{ $disposal?->disposal_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong>.
    </p>

    <div style="background:#f9fafb; border:1px solid #e5e7eb; padding:10px 14px; margin-bottom:10px;">
        <h2 style="font-size:10pt; font-weight:700; color:#1f2937; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #d1d5db;">Désignation de l'actif</h2>
        <table style="width:100%; border-collapse:collapse; font-size:9.5pt;">
            <tr>
                <td style="width:50%; padding:3px 0;"><span style="font-weight:600; color:#6b7280; text-transform:uppercase; font-size:8pt;">Nom de l'actif :</span><br>{{ $asset->name }}</td>
                <td style="width:50%; padding:3px 0;"><span style="font-weight:600; color:#6b7280; text-transform:uppercase; font-size:8pt;">Catégorie :</span><br>{{ $asset->category->name }}</td>
            </tr>
            <tr>
                <td style="padding:3px 0;"><span style="font-weight:600; color:#6b7280; text-transform:uppercase; font-size:8pt;">Numéro de série :</span><br>{{ $asset->serial_number ?: 'Non spécifié' }}</td>
                <td style="padding:3px 0;"><span style="font-weight:600; color:#6b7280; text-transform:uppercase; font-size:8pt;">Date d'acquisition :</span><br>{{ $asset->purchase_date->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    @php
        $lastDepreciation = $asset->depreciations()->where('is_passed', true)->orderByDesc('period_date')->first();
        $vnc = $lastDepreciation ? $lastDepreciation->remaining_vnc : ($asset->purchase_price - $asset->salvage_value);
    @endphp

    <div style="background:#fef2f2; border:1px solid #fecaca; padding:10px 14px; margin-bottom:10px;">
        <h2 style="font-size:10pt; font-weight:700; color:#991b1b; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #fca5a5;">Conditions de sortie</h2>
        <table style="width:100%; border-collapse:collapse; font-size:9.5pt;">
            <tr>
                <td style="width:50%; padding:3px 0;"><span style="font-weight:600; color:#7f1d1d; text-transform:uppercase; font-size:8pt;">Valeur Brute d'achat :</span><br><span style="font-size:11pt; font-weight:700;">{{ number_format($asset->purchase_price, 2, ',', ' ') }} €</span></td>
                <td style="width:50%; padding:3px 0;"><span style="font-weight:600; color:#7f1d1d; text-transform:uppercase; font-size:8pt;">Valeur Nette Comptable (VNC) :</span><br><span style="font-size:11pt; font-weight:700;">{{ number_format($vnc, 2, ',', ' ') }} €</span></td>
            </tr>
            @if($disposal)
            <tr>
                <td style="padding:3px 0;"><span style="font-weight:600; color:#7f1d1d; text-transform:uppercase; font-size:8pt;">Prix de cession :</span><br><span style="font-size:11pt; font-weight:700;">{{ number_format($disposal->sale_price, 2, ',', ' ') }} €</span></td>
                <td style="padding:3px 0;">
                    <span style="font-weight:600; color:#7f1d1d; text-transform:uppercase; font-size:8pt;">Résultat (Plus-value / Moins-value) :</span><br>
                    <span style="font-size:11pt; font-weight:700; {{ $disposal->profit_or_loss >= 0 ? 'color:#166534' : 'color:#991b1b' }}">
                        {{ $disposal->profit_or_loss >= 0 ? '+' : '' }}{{ number_format($disposal->profit_or_loss, 2, ',', ' ') }} €
                    </span>
                </td>
            </tr>
            @endif
            <tr>
                <td colspan="2" style="padding-top:6px; border-top:1px solid #fca5a5;">
                    <span style="font-weight:600; color:#7f1d1d; text-transform:uppercase; font-size:8pt;">Motif de la sortie :</span>
                    <p style="margin-top:4px; padding:6px 8px; background:#fff; border:1px solid #fecaca; font-size:9pt; color:#1f2937;">
                        {{ $disposal?->reason ?? 'Cession ou rebut acté(e).' }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top:24px; display:flex; justify-content:space-between;">
        <div style="text-align:center; width:45%;">
            <p style="font-weight:700; color:#374151; margin-bottom:40px; font-size:9pt;">Signature du Responsable Matériel</p>
            <div style="width:160px; border-bottom:1px solid #9ca3af; margin:0 auto;"></div>
        </div>
        <div style="text-align:center; width:45%;">
            <p style="font-weight:700; color:#374151; margin-bottom:40px; font-size:9pt;">Signature de la Direction / Comptabilité</p>
            <div style="width:160px; border-bottom:1px solid #9ca3af; margin:0 auto;"></div>
        </div>
    </div>

</body>
</html>
