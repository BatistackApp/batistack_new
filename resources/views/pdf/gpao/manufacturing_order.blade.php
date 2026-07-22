@extends('pdf.layout')

@section('content')
    <div class="flex justify-between items-start border-b-2 border-blue-batistack pb-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-blue-batistack uppercase mb-2">ORDRE DE FABRICATION</h1>
            <p class="text-xl font-bold text-slate-800">Réf : {{ $order->reference }}</p>
            <p class="text-slate-500 italic mt-1">Généré le {{ $generated_at }}</p>
        </div>
        <div class="text-right flex flex-col items-end">
            <!-- Le QR code généré par chillerlan est un tag SVG ou une chaîne base64 selon outputType -->
            <div class="w-32 h-32 border border-gray-300 rounded p-1 bg-white mb-2 shadow-sm">
                <img src="{{ $qrCode }}" alt="QR Code" class="w-full h-full object-contain">
            </div>
            <span class="px-3 py-1 bg-gray-200 text-gray-700 font-bold rounded-full text-xs uppercase">{{ $order->status->getLabel() }}</span>
        </div>
    </div>

    <div class="mb-10 bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4 uppercase">Produit à fabriquer</h2>
        <div class="flex justify-between items-center">
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $order->item->name }}</p>
                <p class="text-slate-500 mt-1">Réf Article : {{ $order->item->reference }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500 uppercase font-bold">Quantité</p>
                <p class="text-4xl font-black text-blue-batistack">x {{ floatval($order->quantity_planned) }}</p>
            </div>
        </div>
    </div>

    <div class="mb-10">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4 uppercase">Nomenclature (Matières Premières)</h2>
        
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-300">
                    <th class="py-3 px-4 font-bold text-slate-700">Réf</th>
                    <th class="py-3 px-4 font-bold text-slate-700">Composant</th>
                    <th class="py-3 px-4 font-bold text-slate-700 text-right">Quantité Requise</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->requirements as $req)
                <tr class="border-b border-gray-200 {{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                    <td class="py-3 px-4 text-slate-600 font-mono text-sm">{{ $req->item->reference }}</td>
                    <td class="py-3 px-4 font-medium text-slate-800">{{ $req->item->name }}</td>
                    <td class="py-3 px-4 font-bold text-right text-blue-batistack">{{ floatval($req->quantity_required) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-4 text-center text-slate-500 italic">Aucun composant requis pour cet assemblage.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($order->start_date || $order->end_date)
    <div class="grid grid-cols-2 gap-6 mt-8">
        @if($order->start_date)
        <div class="bg-slate-100 p-4 rounded text-center">
            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Date de début prévue</p>
            <p class="font-bold text-slate-800">{{ $order->start_date->format('d/m/Y') }}</p>
        </div>
        @endif
        
        @if($order->end_date)
        <div class="bg-slate-100 p-4 rounded text-center">
            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Date de fin prévue</p>
            <p class="font-bold text-slate-800">{{ $order->end_date->format('d/m/Y') }}</p>
        </div>
        @endif
    </div>
    @endif
@endsection
