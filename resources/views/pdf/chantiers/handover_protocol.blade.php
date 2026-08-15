@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Procès-Verbal de Réception</h1>
        <p class="text-slate-500 italic">Fin de travaux et transfert de garde</p>
    </div>

    <div class="mb-10 leading-relaxed text-sm">
        <p class="mb-4">
            <strong>Le Maître d'Ouvrage (Client) :</strong> {{ $chantier->client->legal_name ?? $chantier->client->name }}, situé au {{ $chantier->client->addresses->where('is_default', true)->first()?->street ?? '-' }}.
        </p>
        <p class="mb-4">
            <strong>L'Entreprise :</strong> {{ $company->legal_name }}, en qualité de titulaire du marché de travaux pour l'opération **{{ $chantier->name }}** (Réf: {{ $chantier->reference }}).
        </p>
        <p>
            Les parties se sont réunies ce jour pour constater l'achèvement des travaux situés à l'adresse suivante :<br>
            <span class="font-bold">{{ $chantier->address }}, {{ $chantier->zip_code }} {{ $chantier->city }}</span>.
        </p>
    </div>

    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Prononcé de la Réception</h2>
        <div class="space-y-4">
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 border border-black"></div>
                <p><strong>Réception sans réserve :</strong> Le Maître d'Ouvrage déclare accepter les travaux en l'état.</p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 border border-black"></div>
                <p><strong>Réception avec réserves :</strong> Les imperfections ou malfaçons notées ci-dessous devront être levées par l'entreprise.</p>
            </div>
        </div>
    </div>

    <div class="mb-10">
        <h2 class="text-sm font-bold bg-slate-100 text-slate-800 p-2 mb-2 uppercase">Liste des réserves ou travaux de finition :</h2>
        @if($reserves->isNotEmpty())
            <table class="text-[10px] w-full">
                <thead>
                <tr class="bg-slate-100">
                    <th class="text-left p-1">Réf.</th>
                    <th class="text-left p-1">Objet</th>
                    <th class="text-left p-1">Gravité</th>
                    <th class="text-left p-1">Statut</th>
                    <th class="text-left p-1">Assigné à</th>
                    <th class="text-left p-1">Levée le</th>
                </tr>
                </thead>
                <tbody>
                @foreach($reserves as $reserve)
                    <tr class="border-b border-slate-200">
                        <td class="p-1">{{ $reserve->reference }}</td>
                        <td class="p-1">{{ $reserve->title }}</td>
                        <td class="p-1">{{ $reserve->severity?->getLabel() }}</td>
                        <td class="p-1">{{ $reserve->status?->getLabel() }}</td>
                        <td class="p-1">{{ $reserve->assignee?->full_name ?? '-' }}</td>
                        <td class="p-1">{{ $reserve->lifted_at?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="border border-slate-300 h-24 p-4 bg-slate-50 rounded-lg italic text-slate-400">
                Aucune réserve en cours de levée.
            </div>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-10 text-[10px]">
        <div class="border border-slate-200 p-4 rounded-lg bg-blue-50">
            <p class="font-bold mb-1">DÉLAIS DE GARANTIE :</p>
            <ul class="list-disc pl-4 space-y-1">
                <li>Garantie de parfait achèvement (1 an)</li>
                <li>Garantie de bon fonctionnement (2 ans)</li>
                <li>Garantie décennale (10 ans)</li>
            </ul>
        </div>
        <div class="p-4">
            <p>La signature du présent PV marque le point de départ des garanties légales susmentionnées.</p>
        </div>
    </div>

    <div class="mt-16 flex justify-between items-end">
        <div class="text-center w-1/3 relative">
            <p class="font-bold mb-20 text-[10px] uppercase underline">Le Maître d'Ouvrage (Client)</p>
            <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
            <div class="absolute bottom-0 w-full text-center text-transparent text-[8px]">
                @{{Signature;role=Signataire;type=signature}}
            </div>
        </div>
        <div class="text-center w-1/3 text-[8px] text-slate-400 italic">
            Fait à {{ $chantier->city }}, le {{ now()->format('d/m/Y') }}
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">L'Entreprise ({{ $company->legal_name }})</p>
        </div>
    </div>
@endsection
