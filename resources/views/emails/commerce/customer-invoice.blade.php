<p>Bonjour,</p>

<p>Veuillez trouver en piece jointe la facture {{ $invoice->reference }}.</p>

<p>
    Montant TTC : {{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} EUR<br>
    Echeance : {{ $invoice->due_date?->format('d/m/Y') ?? 'Non definie' }}
</p>

<p>Merci pour votre confiance.</p>
