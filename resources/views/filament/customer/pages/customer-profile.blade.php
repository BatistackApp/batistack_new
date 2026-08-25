<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Infos entreprise --}}
        @if ($thirdParty)
            <x-filament::section heading="Informations entreprise">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-filament::section.heading>Raison sociale</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $thirdParty['name'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>Dénomination légale</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $thirdParty['legal_name'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>Email</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $thirdParty['email'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>SIREN</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $thirdParty['siren'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>SIRET</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $thirdParty['siret'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>N° TVA</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $thirdParty['vat_number'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>Téléphone</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $thirdParty['phone'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>IBAN</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $thirdParty['iban'] ?? '—' }}</p>
                    </div>
                    <div>
                        <x-filament::section.heading>BIC / SWIFT</x-filament::section.heading>
                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $thirdParty['bic'] ?? '—' }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Contacts --}}
        <x-filament::section heading="Contacts">
            @if (count($contacts) > 0)
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($contacts as $contact)
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                        {{ strtoupper(substr($contact['first_name'] ?? '', 0, 1).substr($contact['last_name'] ?? '', 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $contact['first_name'] ?? '' }} {{ $contact['last_name'] ?? '' }}
                                        @if ($contact['is_primary'] ?? false)
                                            <span class="ml-2 inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-400">Principal</span>
                                        @endif
                                    </p>
                                    @if ($contact['job_title'] ?? null)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $contact['job_title'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                @if ($contact['email'] ?? null)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-envelope class="w-4 h-4" />
                                        {{ $contact['email'] }}
                                    </span>
                                @endif
                                @if ($contact['phone'] ?? null)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-phone class="w-4 h-4" />
                                        {{ $contact['phone'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun contact enregistré.</p>
            @endif
        </x-filament::section>

        {{-- Adresses --}}
        <x-filament::section heading="Adresses">
            @if (count($addresses) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($addresses as $address)
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-white/10">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $address['type'] ?? 'Autre' }}
                                </span>
                                @if ($address['is_default'] ?? false)
                                    <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-400">Défaut</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $address['street'] ?? '' }}</p>
                            @if ($address['complement'] ?? null)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address['complement'] }}</p>
                            @endif
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address['zip_code'] ?? '' }} {{ $address['city'] ?? '' }}</p>
                            @if ($address['country'] ?? null)
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $address['country'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune adresse enregistrée.</p>
            @endif
        </x-filament::section>

        {{-- Mot de passe --}}
        <x-filament::section heading="Changer le mot de passe">
            <form wire:submit="savePasswordData">
                {{ $this->passwordForm }}

                <div class="mt-4">
                    <x-filament::button type="submit" color="warning">
                        Changer le mot de passe
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
