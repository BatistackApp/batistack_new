<x-mail::message>
# Bonjour {{ $name }},

Vous avez reçu une demande de signature de la part de Batistack.

Veuillez cliquer sur le bouton ci-dessous pour consulter votre document en toute sécurité et apposer votre signature numérique.

<x-mail::button :url="route('signature.show', $signature->token)">
Signer le Document
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
