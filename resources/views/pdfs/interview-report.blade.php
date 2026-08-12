<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte Rendu d'Entretien</title>
    <!-- Use Tailwind CSS CDN for the PDF generation -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
    </style>
</head>
<body class="bg-white text-gray-800 p-8">
    <div class="max-w-4xl mx-auto">
        <header class="border-b-2 border-blue-600 pb-6 mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Compte Rendu d'Entretien</h1>
                <p class="text-xl text-gray-600 mt-2 capitalize">{{ $interview->type->getLabel() }}</p>
            </div>
            <div class="text-right">
                <p class="text-gray-500 font-semibold">Réalisé le : {{ $interview->scheduled_at->format('d/m/Y') }}</p>
            </div>
        </header>

        <section class="grid grid-cols-2 gap-8 mb-8">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h2 class="text-lg font-bold text-blue-800 mb-4 uppercase tracking-wider">Collaborateur</h2>
                <p class="mb-2"><span class="font-semibold">Nom :</span> {{ $interview->employee->first_name }} {{ $interview->employee->last_name }}</p>
                <p><span class="font-semibold">Email :</span> {{ $interview->employee->email }}</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h2 class="text-lg font-bold text-blue-800 mb-4 uppercase tracking-wider">Manager</h2>
                <p class="mb-2"><span class="font-semibold">Nom :</span> {{ $interview->manager->name }}</p>
                <p><span class="font-semibold">Email :</span> {{ $interview->manager->email }}</p>
            </div>
        </section>

        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">Grille d'Évaluation</h2>
            @if($interview->evaluation_grid)
                <div class="space-y-6">
                @foreach($interview->evaluation_grid as $item)
                    <div class="bg-white p-4 border-l-4 border-blue-500 shadow-sm">
                        <h3 class="font-bold text-lg text-gray-800">{{ $item['question'] ?? 'Question' }}</h3>
                        <p class="mt-2 text-gray-600">{{ $item['answer'] ?? 'Non renseigné' }}</p>
                    </div>
                @endforeach
                </div>
            @else
                <p class="text-gray-500 italic">Aucune donnée d'évaluation fournie.</p>
            @endif
        </section>

        <section class="mt-16 grid grid-cols-2 gap-12">
            <div class="text-center">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Signature Employé</h3>
                @if($interview->employee_signature)
                    <img src="{{ $interview->employee_signature }}" alt="Signature Employé" class="mx-auto max-h-32">
                @else
                    <p class="text-gray-400 italic py-8">Non signé</p>
                @endif
            </div>
            <div class="text-center">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Signature Manager</h3>
                @if($interview->manager_signature)
                    <img src="{{ $interview->manager_signature }}" alt="Signature Manager" class="mx-auto max-h-32">
                @else
                    <p class="text-gray-400 italic py-8">Non signé</p>
                @endif
            </div>
        </section>
        
        <footer class="mt-16 text-center text-sm text-gray-400 border-t pt-4">
            Généré par Batistack - Entretien ID #{{ $interview->id }}
        </footer>
    </div>
</body>
</html>
