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
        <div class="border border-slate-300 h-48 p-4 bg-slate-50 rounded-lg italic text-slate-400">
            Mentionnez ici les éventuelles réserves techniques...
        </div>
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
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">Le Maître d'Ouvrage (Client)</p>
        </div>
        <div class="text-center w-1/3 text-[8px] text-slate-400 italic">
            Fait à {{ $chantier->city }}, le {{ now()->format('d/m/Y') }}
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">L'Entreprise ({{ $company->legal_name }})</p>
        </div>
    </div>
@endsection
