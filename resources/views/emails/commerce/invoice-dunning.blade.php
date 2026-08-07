<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .content { margin-bottom: 20px; }
        .footer { font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
        .alert { padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>
                @if($level === 1)
                    Relance Amiable
                @elseif($level === 2)
                    Seconde Relance
                @elseif($level === 3)
                    <span style="color: red;">MISE EN DEMEURE</span>
                @endif
            </h2>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p>Sauf erreur ou omission de notre part, le paiement de la facture <strong>{{ $invoice->reference }}</strong>, d'un montant de <strong>{{ number_format($invoice->total_ttc, 2, ',', ' ') }} € TTC</strong> et venue à échéance le <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>, ne nous est pas parvenu.</p>

            @if($level === 1)
                <p>Nous vous prions de bien vouloir procéder à son règlement dans les meilleurs délais.</p>
                <p>Si ce règlement a été effectué entre-temps, veuillez ne pas tenir compte de ce message.</p>
            @elseif($level === 2)
                <div class="alert">
                    <p>En l'absence de règlement de votre part malgré notre précédente relance, nous vous demandons de procéder au paiement immédiat de cette facture.</p>
                </div>
            @elseif($level === 3)
                <div class="alert">
                    <p><strong>Ceci est une mise en demeure.</strong></p>
                    <p>À défaut de règlement sous 48 heures, nous serons contraints de transmettre ce dossier à notre service contentieux.</p>
                    <p>Conformément à la loi LME et à nos conditions générales de vente, une indemnité forfaitaire pour frais de recouvrement de 40€ ainsi que des pénalités de retard ont été ajoutées au solde dû.</p>
                </div>
            @endif

            <p>Nous restons à votre disposition pour toute information complémentaire.</p>
            <p>Cordialement,</p>
            <p>Le service comptabilité</p>
        </div>

        <div class="footer">
            <p>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</p>
        </div>
    </div>
</body>
</html>
