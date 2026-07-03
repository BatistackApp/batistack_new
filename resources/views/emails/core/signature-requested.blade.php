<x-mail::message>
# Bonjour {{ $name }},

Vous avez reçu une demande de signature de la part de Batistack.

Veuillez consulter le document ci-joint et cliquer sur le lien ci-dessous pour apposer votre signature numérique.

<x-mail::button :url="route('signature.show', $signature->token)">
Signer le Document
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
