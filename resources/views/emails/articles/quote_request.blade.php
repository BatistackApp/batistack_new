<x-mail::message>
# Demande de prix et disponibilité

Bonjour,

Nous souhaiterions connaître vos disponibilités ainsi que votre meilleur tarif pour l'article suivant :

**Référence de votre catalogue :** {{ $item->reference }}  
**Désignation :** {{ $item->name }}  

Pourriez-vous nous faire parvenir une cotation ?

En vous remerciant par avance pour votre retour rapide.

Cordialement,

**L'équipe Achat**  
{{ $company ? $company->name : config('app.name') }}  
{{ $company ? $company->phone : '' }}
</x-mail::message>
