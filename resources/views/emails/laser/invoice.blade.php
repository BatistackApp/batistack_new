<!DOCTYPE html>
<html lang="fr">
<body>
    <p>Bonjour {{ $client?->name ?? 'Madame, Monsieur' }},</p>

    <p>Veuillez trouver ci-joint votre facture {{ $invoice->reference }}.</p>

    <p>Merci de votre confiance.</p>

    <p>Cordialement,<br>{{ config('app.name') }}</p>
</body>
</html>
