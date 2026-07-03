<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Batistack</h1>
            <h2 class="text-2xl font-bold text-gray-700">Dossier d'embauche</h2>
            <p class="mt-2 text-gray-500">
                Bienvenue {{ $employee->first_name }} ! Veuillez compléter votre dossier pour finaliser votre embauche.
            </p>
        </div>

        @if($isCompleted)
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden p-8 text-center border-t-4 border-green-500">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Dossier complété avec succès !</h3>
                <p class="text-gray-600">
                    Merci d'avoir renseigné vos informations. Notre service RH va maintenant générer votre contrat de travail.
                    Vous recevrez très prochainement un email pour le signer électroniquement.
                </p>
            </div>
        @else
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden p-6 sm:p-8">
                <form wire:submit="submit">
                    {{ $this->form }}

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200 ease-in-out transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            Valider mon dossier
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
