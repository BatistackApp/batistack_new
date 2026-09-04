<x-mail::message>
# Bonjour {{ $signer->name }},

Vous avez été désigné comme **{{ $signer->role }}** pour signer un document.

Cliquez sur le bouton ci-dessous pour consulter le document en toute sécurité et apposer votre signature numérique.

@if($signer->signature->signers->count() > 1)
**Signataires ({{ $signer->signature->signed_count }}/{{ $signer->signature->total_signers }} ayant signé) :**
@foreach($signer->signature->signers as $s)
- {{ $s->name }} ({{ $s->role }}) — {{ $s->status->getLabel() }}
@endforeach
@endif

<x-mail::button :url="route('signature.show', $signer->token)">
Signer le Document
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
