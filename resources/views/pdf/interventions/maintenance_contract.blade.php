@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Contrat d'Entretien</h1>
        <p class="text-slate-500 italic">{{ $contract->name }}</p>
        <p class="text-xs text-slate-400">Référence : {{ $contract->reference }}</p>
    </div>

    <div class="mb-10 leading-relaxed text-sm">
        <p class="mb-2">
            <strong>Entre les soussignés :</strong>
        </p>
        <p class="mb-4">
            <strong>Le Client :</strong>
            {{ $contract->thirdParty->legal_name ?? $contract->thirdParty->name }}, situé
            @if ($contract->thirdParty->addresses->where('is_default', true)->first()?->street)
                au {{ $contract->thirdParty->addresses->where('is_default', true)->first()?->street }},
            @endif
            ci-après dénommé « le Client ».
        </p>
        <p class="mb-4">
            <strong>Le Prestataire :</strong>
            {{ $company->legal_name }}, dont le siège est situé {{ $company->address }},
            ci-après dénommé « l'Entreprise ».
        </p>
        <p>
            Il a été convenu ce qui suit, en vue d'assurer la maintenance préventive de l'équipement désigné ci-dessous.
        </p>
    </div>

    <div class="avoid-break mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">1. Objet du contrat</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="border border-slate-200 p-3 rounded-lg">
                <p class="text-[10px] uppercase text-slate-400">Équipement</p>
                <p class="font-semibold">{{ $contract->clientEquipment->name ?? '-' }}</p>
                @if ($contract->clientEquipment->brand)
                    <p class="text-xs text-slate-500">Marque : {{ $contract->clientEquipment->brand }}</p>
                @endif
                @if ($contract->clientEquipment->serial_number)
                    <p class="text-xs text-slate-500">N° de série : {{ $contract->clientEquipment->serial_number }}</p>
                @endif
            </div>
            <div class="border border-slate-200 p-3 rounded-lg">
                <p class="text-[10px] uppercase text-slate-400">Chantier / Site</p>
                <p class="font-semibold">{{ $contract->chantier?->name ?? 'Non précisé' }}</p>
                @if ($contract->chantier)
                    <p class="text-xs text-slate-500">{{ $contract->chantier->address }}, {{ $contract->chantier->city }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="avoid-break mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">2. Prestations et durée</h2>
        <table class="text-[11px] w-full mb-4">
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="p-2 w-1/3 text-slate-500">Fréquence des visites</td>
                    <td class="p-2 font-semibold">{{ $contract->frequency?->getLabel() }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 text-slate-500">Début du contrat</td>
                    <td class="p-2">{{ $contract->start_date?->format('d/m/Y') }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 text-slate-500">Fin du contrat</td>
                    <td class="p-2">{{ $contract->end_date?->format('d/m/Y') ?? 'Durée indéterminée' }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2 text-slate-500">Forfait annuel (HT)</td>
                    <td class="p-2 font-bold">{{ number_format((float) $contract->flat_rate_price, 2, ',', ' ') }} € HT</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($contract->description)
        <div class="avoid-break mb-8">
            <h2 class="text-sm font-bold bg-slate-100 text-slate-800 p-2 mb-2 uppercase">3. Description de la prestation</h2>
            <div class="text-sm leading-relaxed whitespace-pre-line">{{ $contract->description }}</div>
        </div>
    @endif

    <div class="avoid-break grid grid-cols-2 gap-10 text-[10px] mb-10">
        <div class="border border-slate-200 p-4 rounded-lg bg-blue-50">
            <p class="font-bold mb-1">ENGAGEMENTS DE L'ENTREPRISE :</p>
            <ul class="list-disc pl-4 space-y-1">
                <li>Réalisation des visites de maintenance préventive à la fréquence convenue.</li>
                <li>Intervention sur l'équipement désigné par du personnel qualifié.</li>
                <li>Compte-rendu d'intervention après chaque visite.</li>
            </ul>
        </div>
        <div class="border border-slate-200 p-4 rounded-lg">
            <p class="font-bold mb-1">ENGAGEMENTS DU CLIENT :</p>
            <ul class="list-disc pl-4 space-y-1">
                <li>Faciliter l'accès à l'équipement et aux installations.</li>
                <li>Informer l'Entreprise de toute anomalie constatée.</li>
                <li>Respecter les conditions de règlement des forfaits convenus.</li>
            </ul>
        </div>
    </div>

    <div class="avoid-break mb-10 text-[10px] leading-relaxed">
        <p class="font-bold mb-1">4. Conditions financières :</p>
        <p>
            Le présent contrat est conclu au prix forfaitaire de
            <strong>{{ number_format((float) $contract->flat_rate_price, 2, ',', ' ') }} € HT</strong>,
            payable selon les modalités convenues entre les parties. Les éventuelles pièces détachées et fournitures
            nécessaires aux réparations non couvertes par le forfait sont facturées en sus.
        </p>
    </div>

    <div class="avoid-break mt-16 flex justify-between items-end">
        <div class="text-center w-1/3 relative">
            <p class="font-bold mb-20 text-[10px] uppercase underline">Le Client</p>
            <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
            <div class="absolute bottom-0 w-full text-center text-transparent text-[8px]">
                @{{Signature;role=Signataire;type=signature}}
            </div>
        </div>
        <div class="text-center w-1/3 text-[8px] text-slate-400 italic">
            Fait à {{ $company->city ?? '________________' }}, le {{ now()->format('d/m/Y') }}
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">L'Entreprise ({{ $company->legal_name }})</p>
        </div>
    </div>
@endsection