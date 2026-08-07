@component('layouts.public')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
            <div>
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                @if($isPending ?? false)
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Paiement en cours...
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Votre transaction (virement) a bien été initiée. Votre facture sera validée dès réception des fonds par notre banque.
                    </p>
                @else
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Paiement réussi !
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Merci pour votre paiement. Votre facture est désormais réglée et sera mise à jour dans notre système.
                    </p>
                @endif
            </div>
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
@endcomponent
