@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-end mb-8 border-b-4 border-blue-batistack pb-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-batistack uppercase">Journal de Chantier Hebdomadaire</h1>
            <p class="text-lg font-bold text-slate-700">{{ $chantier->name }}</p>
            <p class="text-sm text-slate-500">{{ $period }}</p>
        </div>
        <div class="text-right text-[10px]">
            <p><strong>Conducteur :</strong> {{ $chantier->manager?->full_name ?? '-' }}</p>
            <p><strong>Réf :</strong> {{ $chantier->reference }}</p>
        </div>
    </div>

    @forelse($logs->groupBy(fn($log) => $log->date->format('Y-m-d')) as $date => $dayLogs)
        <div class="mb-6 avoid-page-break">
            <div class="bg-slate-800 text-white p-2 flex justify-between items-center rounded-t-lg">
                <h2 class="text-xs font-bold uppercase">{{ \Carbon\Carbon::parse($date)->translatedFormat('l d F Y') }}</h2>
                <div class="flex space-x-4 text-[9px]">
                    <span>🌦 Météo : <strong>{{ $dayLogs->first()->weather_condition ?? 'Non renseignée' }}</strong></span>
                    @if($dayLogs->contains('incident_reported', true))
                        <span class="bg-red-500 px-2 py-0.5 rounded font-bold animate-pulse">⚠️ INCIDENT SIGNALÉ</span>
                    @endif
                </div>
            </div>

            <div class="border border-slate-200 border-t-0 p-4 rounded-b-lg bg-white">
                @foreach($dayLogs as $log)
                    <div class="mb-4 last:mb-0">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-[8px] font-bold text-slate-400 uppercase">Saisie par {{ $log->user->name }}</span>
                            <span class="text-[8px] text-slate-400 italic">{{ $log->created_at->format('H:i') }}</span>
                        </div>
                        <div class="text-[10px] text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {!! nl2br(e($log->content)) !!}
                        </div>
                        @if($log->hasMedia('photos'))
                            <div class="mt-3 grid grid-cols-4 gap-2">
                                @foreach($log->getMedia('photos') as $photo)
                                    <div class="h-24 bg-slate-100 border rounded overflow-hidden">
                                        <img src="{{ $photo->getPath() }}" class="object-cover w-full h-full">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- EFFECTIFS DU JOUR -->
                @php
                    $present = $chantier->timeEntries()
                        ->whereDate('date', $date)
                        ->with('employee')
                        ->get()
                        ->unique('employee_id');
                @endphp
                @if($present->isNotEmpty())
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <p class="text-[8px] font-bold text-slate-500 uppercase mb-1">Effectifs présents :</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($present as $entry)
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-[9px] text-slate-600 border border-slate-200">
                                    {{ $entry->employee->full_name }} ({{ $entry->hours }}h)
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="py-20 text-center border-2 border-dashed border-slate-200 rounded-2xl">
            <p class="text-slate-400 italic">Aucune note saisie pour cette période.</p>
        </div>
    @endforelse

    <footer class="fixed bottom-0 left-0 w-full p-8 text-[7px] text-slate-400 border-t border-slate-100 flex justify-between">
        <div>Journal d'activité légal - Batistack ERP</div>
        <div class="uppercase">Page 1 sur 1 - Horodatage : {{ $generated_at }}</div>
    </footer>
@endsection
