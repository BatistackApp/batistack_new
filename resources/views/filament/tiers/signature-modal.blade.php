<div class="space-y-4">
    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Statut</p>
                <p class="text-sm font-bold text-green-600">Signé et Certifié</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Date et heure (UTC)</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $signature->signed_at?->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Adresse IP</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $signature->ip_address }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Token Unique</p>
                <p class="text-xs text-gray-900 dark:text-white break-all">{{ $signature->token }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Empreinte SHA-256</p>
                <p class="text-xs text-gray-900 dark:text-white break-all">{{ $signature->checksum }}</p>
            </div>
        </div>
    </div>

    @if($signature->signature_data)
        <div>
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">Signature Tracée :</p>
            <div class="border border-gray-300 rounded-lg bg-white p-2 w-full max-w-sm flex justify-center">
                <img src="{{ $signature->signature_data }}" alt="Signature" class="max-h-32 object-contain" />
            </div>
        </div>
    @endif
</div>
