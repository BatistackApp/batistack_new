@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-center mb-8 border-b-2 border-blue-batistack pb-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-batistack uppercase">{{ $title }}</h1>
            <p class="text-slate-500">Document généré le {{ $generated_at }}</p>
        </div>
        <div class="text-right">
            <span class="font-bold">{{ $company['name'] }}</span><br>
            <span class="text-xs">SIRET : {{ $company['siret'] }}</span><br>
            <span class="text-xs">Adresse: {{ $company['address'] }}</span>
        </div>
    </div>

    <table class="text-[10px]">
        <thead>
        <tr>
            <th>Raison Sociale / Nom</th>
            <th>SIRET</th>
            <th>Type</th>
            <th>Email / Contact</th>
            <th>Vigilance</th>
            <th>Statut</th>
        </tr>
        </thead>
        <tbody>
        @foreach($thirdParties as $tp)
            <tr>
                <td class="font-bold">{{ $tp->name }}</td>
                <td class="font-mono">{{ $tp->siret }}</td>
                <td>{{ $tp->type->getLabel() }}</td>
                <td>{{ $tp->email ?? 'N/A' }}</td>
                <td class="text-center">
                    @php $compliance = app(\App\Services\Tiers\VigilanceService::class)->scanCompliance($tp); @endphp
                    <span @class([
                            'px-2 py-1 rounded-full text-white font-bold',
                            'bg-green-600' => $compliance['compliant'],
                            'bg-red-600' => !$compliance['compliant'],
                        ])>
                            {{ $compliance['compliant'] ? 'CONFORME' : 'ALERTE' }}
                        </span>
                </td>
                <td>
                    {{ $tp->is_active ? 'Actif' : 'Inactif' }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-8 text-center text-slate-400 text-[8px]">
        Ce document est un état interne Batistack. Les informations de conformité sont indicatives au moment de la génération.
    </div>
@endsection
