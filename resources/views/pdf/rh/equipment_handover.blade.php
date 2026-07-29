@extends('pdf.layout')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-blue-batistack border-b-2 border-blue-batistack pb-2 uppercase">Bon de Remise de Matériel & EPI</h1>
        <div class="flex justify-between mt-4">
            <p><strong>Bénéficiaire :</strong> {{ $employee->full_name }}</p>
            <p><strong>Date de remise :</strong> {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <p class="mb-6 text-sm">Le salarié reconnaît avoir reçu les équipements et outils listés ci-dessous en bon état de fonctionnement et s'engage à en prendre soin et à les restituer lors de son départ.</p>

    <table>
        <thead>
        <tr>
            <th class="text-left">Catégorie</th>
            <th class="text-left">Désignation / Modèle</th>
            <th class="text-left">N° Série / Réf</th>
            <th class="text-center">Date Échéance</th>
        </tr>
        </thead>
        <tbody>
        @foreach($equipments as $item)
            <tr>
                <td class="py-2">{{ $item->type->getLabel() }}</td>
                <td class="font-bold">{{ $item->label }}</td>
                <td class="font-mono">{{ $item->serial_number ?? '-' }}</td>
                <td class="text-center text-slate-500">{{ $item->expires_at?->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-12 p-4 border rounded-lg bg-gray-header text-[10px]">
        <h4 class="font-bold mb-2">ENGAGEMENT DU SALARIÉ :</h4>
        <p>Je m'engage à utiliser les équipements de protection individuelle (EPI) conformément aux règles de sécurité en vigueur. En cas de perte, de vol ou de dégradation anormale de l'outillage confié, ma responsabilité pourra être engagée.</p>
    </div>

    <div class="mt-16 flex justify-around">
        <div class="border-t pt-2 w-48 text-center">Signature Employeur</div>
        <div class="border-t pt-2 w-48 text-center relative">
            Signature Salarié
            <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
            <div class="absolute bottom-6 left-0 right-0 text-[8px] text-transparent">
                @{{Signature;role=Signataire;type=signature}}
            </div>
        </div>
    </div>
@endsection
