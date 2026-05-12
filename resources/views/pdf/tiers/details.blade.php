@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-12">
        <div class="w-1/2">
            <h1 class="text-3xl font-bold text-blue-batistack mb-2">{{ $thirdParty->name }}</h1>
            <p class="text-lg text-slate-600 mb-4">{{ $thirdParty->legal_name ?? 'Sans raison sociale spécifiée' }}</p>

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <p><strong>SIRET :</strong> <span class="font-mono">{{ $thirdParty->siret }}</span></p>
                <p><strong>TVA :</strong> <span class="font-mono">{{ $thirdParty->vat_number }}</span></p>
                <p><strong>Type :</strong> {{ $thirdParty->type->getLabel() }}</p>
            </div>
        </div>

        @if($thirdParty->type->value === \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR->value)
            <div class="w-1/3 text-right">
                <div @class([
                'p-4 rounded-lg border-2 text-center uppercase font-bold',
                'border-green-600 text-green-600 bg-green-50' => $compliance['compliant'],
                'border-red-600 text-red-600 bg-red-50' => !$compliance['compliant'],
            ])>
                    <p class="text-xs">Statut Vigilance</p>
                    <p class="text-xl">{{ $compliance['compliant'] ? 'Conforme' : 'Non-Conforme' }}</p>
                </div>
                <p class="text-[10px] mt-2 text-slate-500">Généré le {{ $generated_at }}</p>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8">
        <section>
            <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Siège & Adresses</h2>
            @foreach($thirdParty->addresses as $address)
                <div class="mb-3 pb-2 border-b border-slate-100 last:border-0">
                    <p class="font-bold text-xs">{{ $address->type->getLabel() }} @if($address->is_default)
                            <span class="text-blue-600">(Défaut)</span>
                        @endif</p>
                    <p>{{ $address->street }}</p>
                    <p>{{ $address->zip_code }} {{ $address->city }}</p>
                </div>
            @endforeach
        </section>

        <section>
            <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Interlocuteurs</h2>
            @foreach($thirdParty->contacts as $contact)
                <div class="mb-3 pb-2 border-b border-slate-100 last:border-0">
                    <p class="font-bold text-xs">{{ $contact->full_name }} @if($contact->is_primary)
                            <span class="text-blue-600">(Principal)</span>
                        @endif</p>
                    <p class="text-slate-500">{{ $contact->job_title }}</p>
                    <p>{{ $contact->email }}</p>
                    <p>{{ $contact->phone }}</p>
                </div>
            @endforeach
        </section>
    </div>

    @if($thirdParty->type->value === \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR->value)
        <section class="mb-8">
            <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Analyse de Conformité
                détaillée</h2>
            @if(empty($compliance['issues']))
                <p class="text-green-600 font-bold">Tous les documents sont présents et à jour.</p>
            @else
                <ul class="list-disc pl-5 text-red-600">
                    @foreach($compliance['issues'] as $issue)
                        <li class="mb-1">{{ $issue }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <footer class="fixed bottom-0 left-0 w-full text-center text-slate-400 text-[8px] py-4 border-t border-slate-100">
        {{ $company['name'] ?? 'Batistack' }} - Fiche Tiers Certifiée - Page 1/1
    </footer>
@endsection
