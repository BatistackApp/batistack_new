@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-8 border-b-2 border-blue-batistack pb-6">
        <div class="w-2/3">
            <h1 class="text-3xl font-bold text-blue-batistack uppercase mb-1">Passeport Sécurité Collaborateur</h1>
            <p class="text-slate-500 font-bold text-lg italic">Certificat d'Aptitudes et Habilitations BTP</p>

            <div class="mt-6 flex items-center space-x-6">
                <div class="h-24 w-24 rounded-lg bg-slate-100 border-2 border-slate-200 overflow-hidden">
                    @if($employee->hasMedia('avatar'))
                        <img src="{{ $employee->getFirstMediaUrl('avatar') }}" class="object-cover h-full w-full">
                    @endif
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ $employee->full_name }}</p>
                    <p class="text-slate-600">Matricule : <span class="font-mono">{{ $employee->registration_number }}</span></p>
                    <p class="text-blue-batistack font-bold uppercase text-xs">{{ $employee->currentContract?->job_title }}</p>
                </div>
            </div>
        </div>

        <!-- ZONE QR CODE POUR VÉRIFICATION TEMPS RÉEL -->
        <div class="w-1/4 text-center">
            <div class="p-2 bg-white border-2 border-slate-100 rounded-xl shadow-sm inline-block">
                <img src="{{ (new \chillerlan\QRCode\QRCode)->render($publicUrl) }}" width="100" height="100">
            </div>
            <p class="text-[8px] text-slate-400 mt-2 uppercase font-bold tracking-tighter">Scanner pour vérifier la validité<br>en temps réel</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-10">
        <!-- Aptitude Médicale -->
        <section>
            <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-4 uppercase flex justify-between">
                <span>Suivi Médical Réglementaire</span>
                <i class="fa-solid fa-heart-pulse"></i>
            </h2>
            @php $lastVisit = $employee->medicalVisits()->latest('visit_date')->first(); @endphp
            @if($lastVisit)
                <div class="bg-gray-header p-4 rounded-lg border border-slate-200">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Nature de la visite :</span>
                        <span class="font-bold">{{ $lastVisit->type->getLabel() }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Date de visite :</span>
                        <span>{{ $lastVisit->visit_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Statut Aptitude :</span>
                        <span @class([
                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                        'bg-green-100 text-green-700' => $lastVisit->aptitude->value === 'fit',
                        'bg-orange-100 text-orange-700' => $lastVisit->aptitude->value === 'fit_restricted',
                    ])>
                        {{ $lastVisit->aptitude->getLabel() }}
                    </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-300 flex justify-between items-center">
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Prochaine Échéance :</span>
                        <span @class(['font-bold', 'text-red-600' => $lastVisit->isExpired()])>
                        {{ $lastVisit->next_due_date->format('d/m/Y') }}
                    </span>
                    </div>
                </div>
            @else
                <p class="text-red-500 italic text-xs">Aucune donnée médicale enregistrée.</p>
            @endif
        </section>

        <!-- Habilitations & CACES -->
        <section>
            <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-4 uppercase flex justify-between">
                <span>Habilitations Techniques & CACES</span>
                <i class="fa-solid fa-certificate"></i>
            </h2>
            <table class="text-[9px]">
                <thead>
                <tr>
                    <th class="text-left py-2">Habilitation</th>
                    <th class="text-center py-2">Référence</th>
                    <th class="text-right py-2">Validité</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employee->qualifications as $qualif)
                    <tr>
                        <td class="py-2 font-bold">{{ $qualif->label }}</td>
                        <td class="text-center font-mono text-slate-500">{{ $qualif->reference_number ?? '-' }}</td>
                        <td @class(['text-right', 'text-red-600 font-bold' => $qualif->expires_at->isPast()])>
                            {{ $qualif->expires_at->format('d/m/Y') }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <div class="mt-12 p-4 bg-blue-50 border border-blue-100 rounded-lg flex items-start space-x-4">
        <div class="text-blue-batistack text-2xl pt-1">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="text-[9px] leading-tight text-blue-900">
            <p class="font-bold uppercase mb-1">Attestation de validité documentaire</p>
            <p>Ce document est généré par le système Batistack ERP. Il certifie que le collaborateur susnommé est en possession des aptitudes et habilitations listées ci-dessus. En cas de doute sur l'authenticité de ce certificat papier, veuillez scanner le QR Code de sécurité pour accéder à la fiche numérique sécurisée.</p>
        </div>
    </div>

    <footer class="fixed bottom-0 left-0 w-full flex justify-between items-end p-8 text-slate-400 text-[8px]">
        <div>Généré le {{ $generated_at }}</div>
        <div class="text-right uppercase font-bold tracking-widest">Batistack Safety Protocol v1.0</div>
    </footer>
@endsection
