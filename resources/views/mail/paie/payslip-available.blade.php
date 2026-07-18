<x-mail::message>
# Bonjour {{ $payslip->employee->first_name }},

Votre bulletin de salaire pour la période **{{ $payslip->period }}** est désormais disponible sur votre espace sécurisé Batistack.

Afin de garantir la confidentialité de vos données, le document n'est pas joint à cet email. Vous pouvez le consulter et le télécharger à tout moment en vous connectant à votre portail salarié.

<x-mail::button :url="url('/salarie')">
Accéder à mon espace
</x-mail::button>

Merci,<br>
Le service RH
</x-mail::message>
