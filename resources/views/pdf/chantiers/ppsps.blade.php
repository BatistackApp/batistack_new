@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Plan Particulier de Sécurité et de Protection de la Santé</h1>
        <p class="text-slate-500 italic">PPSPS - Chantier {{ $chantier->reference }} - généré le {{ $generated_at }}</p>
    </div>

    {{-- 1. IDENTITÉ & INTERVENANTS --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">1. Identité et intervenants</h2>
        <table class="text-[10px]">
            <tbody>
            <tr>
                <td class="w-1/3 bg-gray-header font-bold">Chantier</td>
                <td>{{ $chantier->name }} ({{ $chantier->reference }})</td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Adresse</td>
                <td>{{ $chantier->full_address }}</td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Maître d'Ouvrage (Client)</td>
                <td>{{ $client?->legal_name ?? $client?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Entreprise / Conducteur de travaux</td>
                <td>{{ $manager?->full_name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Société</td>
                <td>{{ $company?->legal_name ?? '-' }} - SIRET {{ $company?->siret ?? '-' }}</td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Période</td>
                <td>
                    {{ $chantier->start_date_preview?->format('d/m/Y') ?? '-' }}
                    au
                    {{ $chantier->end_date_preview?->format('d/m/Y') ?? '-' }}
                </td>
            </tr>
            <tr>
                <td class="bg-gray-header font-bold">Avancement global</td>
                <td>{{ $progress }}%</td>
            </tr>
            </tbody>
        </table>

        <h3 class="font-bold text-[10px] mt-4 mb-1 uppercase">Personnel de l'entreprise</h3>
        <table class="text-[10px]">
            <thead>
            <tr>
                <th>Nom</th>
                <th>Fonction</th>
                <th>Visite médicale</th>
                <th>Qualifications / habilitations</th>
            </tr>
            </thead>
            <tbody>
            @forelse($members as $m)
                <tr>
                    <td>{{ $m['employee']->full_name }}</td>
                    <td>{{ $m['employee']->currentContract?->job_title ?? '-' }}</td>
                    <td>
                        @if($m['medical'])
                            {{ $m['medical']->visit_date?->format('d/m/Y') }}
                            @if($m['medical']->isExpired()) <span class="text-red-600 font-bold">(expirée)</span>
                            @else <span class="text-green-600 font-bold">(à jour)</span>@endif
                        @else <span class="text-red-600 font-bold">Manquante</span>@endif
                    </td>
                    <td>
                        @forelse($m['qualifications']->filter->isActive() as $q)
                            {{ $q->type?->getLabel() }}@if(!$loop->last), @endif
                        @empty -
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="italic text-slate-400">Aucun membre assigné.</td></tr>
            @endforelse
            </tbody>
        </table>

        <h3 class="font-bold text-[10px] mt-4 mb-1 uppercase">Entreprises extérieures (sous-traitants)</h3>
        <table class="text-[10px]">
            <tbody>
            @forelse($subcontractors as $s)
                <tr><td>{{ $s->legal_name ?? $s->name }}</td></tr>
            @empty
                <tr><td class="italic text-slate-400">Aucun sous-traitant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- 2. PLANNING & PHASES --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">2. Planning et phases</h2>
        @forelse($phases as $p)
            <h3 class="font-bold text-[10px] mt-3 mb-1 uppercase">{{ $p['phase']->label }}</h3>
            <table class="text-[10px]">
                <thead>
                <tr>
                    <th>Tâche</th>
                    <th>Description</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Avancement</th>
                </tr>
                </thead>
                <tbody>
                @forelse($p['tasks'] as $task)
                    <tr>
                        <td>{{ $task->label }}</td>
                        <td>{{ $task->description ?? '-' }}</td>
                        <td>{{ $task->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $task->end_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $task->progress_percentage ?? 0 }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="italic text-slate-400">Aucune tâche.</td></tr>
                @endforelse
                </tbody>
            </table>
        @empty
            <p class="italic text-slate-400 text-[10px]">Aucune phase planifiée.</p>
        @endforelse
    </div>

    <div class="page-break"></div>

    {{-- 3. MATÉRIEL ALLOUÉ --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">3. Matériel alloué au chantier</h2>
        <table class="text-[10px]">
            <thead>
            <tr>
                <th>Référence</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Unité</th>
            </tr>
            </thead>
            <tbody>
            @forelse($materials as $mat)
                <tr>
                    <td>{{ $mat['item']->reference }}</td>
                    <td>{{ $mat['item']->name }}</td>
                    <td>{{ $mat['quantity'] }}</td>
                    <td>{{ $mat['unit'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="italic text-slate-400">Aucun matériel alloué.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- 4. PRODUITS & FDS --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">4. Produits utilisés et fiches de données de sécurité</h2>
        <table class="text-[10px]">
            <thead>
            <tr>
                <th>Produit</th>
                <th>Catégorie de danger</th>
                <th>Pictogrammes</th>
                <th>Phrases de danger (H)</th>
                <th>Phrases de précaution (P)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->name }}<br><span class="text-slate-400">{{ $product->reference }}</span></td>
                    <td>{{ $product->hazard_category?->getLabel() ?? '-' }}</td>
                    <td>
                        @forelse($product->pictograms() as $pic)
                            <span class="inline-block align-middle">{{ $pic->getGlyph() }}</span>
                        @empty -
                        @endforelse
                    </td>
                    <td>
                        @forelse($product->h_phrases ?? [] as $h)
                            <div>{{ $h }}</div>
                        @empty -
                        @endforelse
                    </td>
                    <td>
                        @forelse($product->p_phrases ?? [] as $p)
                            <div>{{ $p }}</div>
                        @empty -
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="italic text-slate-400">Aucun produit avec fiche de sécurité.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- 5. ANALYSE DE RISQUES --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">5. Analyse des risques par phase</h2>
        <table class="text-[10px]">
            <thead>
            <tr>
                <th>Phase</th>
                <th>Risques identifiés</th>
                <th>Produits concernés</th>
            </tr>
            </thead>
            <tbody>
            @forelse($phases as $p)
                <tr>
                    <td>{{ $p['phase']->label }}</td>
                    <td>
                        @forelse($p['risks'] as $risk)
                            <div>{{ $risk->getLabel() }}</div>
                        @empty
                            <span class="italic text-slate-400">Aucun risque spécifique</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse($p['products'] as $prod)
                            {{ $prod->name }}@if(!$loop->last), @endif
                        @empty -
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="italic text-slate-400">Aucune phase.</td></tr>
            @endforelse
            </tbody>
        </table>

        <h3 class="font-bold text-[10px] mt-4 mb-1 uppercase">Synthèse des risques du chantier</h3>
        <div class="flex flex-wrap gap-2">
            @forelse($risks as $risk)
                <span class="border border-red-300 bg-red-50 text-red-700 px-2 py-1 rounded">{{ $risk->getLabel() }}</span>
            @empty
                <span class="italic text-slate-400 text-[10px]">Aucun risque détecté.</span>
            @endforelse
        </div>
    </div>

    {{-- 6. MESURES DE PRÉVENTION --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold bg-blue-batistack text-white p-2 mb-2 uppercase">6. Mesures de prévention</h2>
        <div class="grid grid-cols-2 gap-4 text-[10px]">
            <div>
                <h3 class="font-bold mb-1 uppercase">Protection collective / organisationnelle</h3>
                <ul class="list-disc pl-4 space-y-1">
                    @forelse($collective as $measure)
                        <li>{{ $measure }}</li>
                    @empty
                        <li class="italic text-slate-400">Aucune mesure collective requise.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="font-bold mb-1 uppercase">Équipements de Protection Individuelle (EPI)</h3>
                <ul class="list-disc pl-4 space-y-1">
                    @forelse($epi as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li class="italic text-slate-400">Aucun EPI spécifique requis.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- 7. SIGNATURES --}}
    <div class="mt-16 flex justify-between items-end">
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">Le Maître d'Ouvrage</p>
        </div>
        <div class="text-center w-1/3 text-[8px] text-slate-400 italic">
            Fait à {{ $chantier->city }}, le {{ $generated_at }}
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-20 text-[10px] uppercase underline">L'Entreprise ({{ $company?->legal_name }})</p>
        </div>
    </div>
@endsection