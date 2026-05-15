@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start mb-10 border-b-4 border-blue-batistack pb-6">
        <div class="flex items-center space-x-6">
            <div class="h-32 w-32 rounded-xl bg-slate-100 border-2 border-slate-200 overflow-hidden shadow-inner">
                @if($employee->hasMedia('avatar'))
                    <img src="{{ $employee->getFirstMediaUrl('avatar') }}" class="object-cover h-full w-full">
                @endif
            </div>
            <div>
                <h1 class="text-4xl font-bold text-blue-batistack">{{ $employee->full_name }}</h1>
                <p class="text-xl text-slate-600 font-medium">Matricule : {{ $employee->registration_number }}</p>
                <div class="mt-2 flex space-x-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">
                    {{ $employee->currentContract?->job_title ?? 'Poste non défini' }}
                </span>
                    <span @class([
                    'px-3 py-1 rounded-full text-xs font-bold uppercase',
                    'bg-green-100 text-green-700' => $employee->is_active,
                    'bg-red-100 text-red-700' => !$employee->is_active,
                ])>
                    {{ $employee->is_active ? 'En poste' : 'Sorti' }}
                </span>
                </div>
            </div>
        </div>
        <div class="text-center">
            <img src="{{ (new \chillerlan\QRCode\QRCode)->render($publicUrl) }}" width="80">
            <p class="text-[7px] text-slate-400 mt-1 uppercase font-bold">Passeport Numérique</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8">
        <!-- BLOC 1 : ÉTAT CIVIL & CONTACT -->
        <section class="bg-gray-header p-5 rounded-2xl border border-slate-200">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-slate-300 pb-2">Identité & Contact</h2>
            <table class="text-[10px] w-full">
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Né(e) le :</td>
                    <td class="text-right">{{ $employee->birth_date?->format('d/m/Y') ?? 'Non renseigné' }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">N° Sécu :</td>
                    <td class="text-right font-mono">{{ $employee->social_security_number ?? '---' }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Email :</td>
                    <td class="text-right">{{ $employee->email }}</td>
                </tr>
                <tr class="h-8">
                    <td class="text-slate-500 font-bold uppercase">Téléphone :</td>
                    <td class="text-right">{{ $employee->phone ?? '---' }}</td>
                </tr>
            </table>
        </section>

        <!-- BLOC 2 : CONTRAT ACTUEL -->
        <section class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
            <h2 class="text-sm font-bold text-blue-batistack uppercase mb-4 border-b border-blue-200 pb-2">Conditions de Travail</h2>
            @if($contract = $employee->currentContract)
                <table class="text-[10px] w-full">
                    <tr class="h-8">
                        <td class="text-blue-900 font-bold uppercase">Type :</td>
                        <td class="text-right font-bold">{{ $contract->type->getLabel() }}</td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-blue-900 font-bold uppercase">Entrée :</td>
                        <td class="text-right">{{ $contract->start_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-blue-900 font-bold uppercase">Base :</td>
                        <td class="text-right">{{ $contract->weekly_hours }} h / semaine</td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-blue-900 font-bold uppercase">Taux Horaire :</td>
                        <td class="text-right font-bold text-blue-700">{{ number_format($contract->hourly_rate, 2) }} € / h</td>
                    </tr>
                </table>
            @else
                <p class="text-red-500 italic text-xs">Aucun contrat actif enregistré.</p>
            @endif
        </section>
    </div>

    <!-- SECTION : MATÉRIEL ET ÉQUIPEMENTS -->
    <div class="mb-8">
        <h2 class="text-xs font-bold bg-slate-800 text-white p-2 mb-4 uppercase">Matériel & EPI Confiés</h2>
        <table class="text-[9px]">
            <thead>
            <tr class="bg-slate-100 text-slate-700">
                <th class="text-left py-2 px-3">Type</th>
                <th class="text-left">Désignation</th>
                <th class="text-left">Réf / S/N</th>
                <th class="text-center">Échéance</th>
            </tr>
            </thead>
            <tbody>
            @forelse($employee->equipements as $eq)
                <tr>
                    <td class="py-2 px-3 font-bold">{{ $eq->type->getLabel() }}</td>
                    <td>{{ $eq->label }}</td>
                    <td class="font-mono">{{ $eq->serial_number ?? '-' }}</td>
                    <td class="text-center">
                    <span @class(['text-red-600 font-bold' => $eq->isExpired()])>
                        {{ $eq->expires_at?->format('d/m/Y') ?? 'N/A' }}
                    </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 italic text-slate-400 text-center">Aucun équipement recensé</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- SECTION : HABILITATIONS & MÉDICAL (Synthèse) -->
    <div class="grid grid-cols-2 gap-8">
        <section>
            <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Habilitations Actives</h2>
            <table class="text-[9px]">
                @foreach($employee->qualifications()->where('expires_at', '>', now())->get() as $q)
                    <tr>
                        <td class="py-2 font-bold">{{ $q->label }}</td>
                        <td class="text-right text-slate-500">Exp. {{ $q->expires_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </table>
        </section>

        <section>
            <h2 class="text-xs font-bold bg-blue-batistack text-white p-2 mb-4 uppercase">Suivi Médical</h2>
            @php $lastVisit = $employee->medicalVisits()->latest('visit_date')->first(); @endphp
            @if($lastVisit)
                <div class="text-[10px] space-y-2">
                    <p><strong>Dernier examen :</strong> {{ $lastVisit->type->getLabel() }} ({{ $lastVisit->visit_date->format('d/m/Y') }})</p>
                    <p><strong>Aptitude :</strong> <span class="font-bold text-green-600">{{ $lastVisit->aptitude->getLabel() }}</span></p>
                    <p><strong>Prochaine visite :</strong> <span class="font-bold">{{ $lastVisit->next_due_date->format('d/m/Y') }}</span></p>
                </div>
            @else
                <p class="text-red-500 italic text-xs">Aucune visite médicale enregistrée.</p>
            @endif
        </section>
    </div>

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[8px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Dossier confidentiel Batistack - Généré le {{ $generated_at }}</div>
        <div class="font-bold uppercase tracking-widest">Employee Record v1.0</div>
    </footer>
@endsection
