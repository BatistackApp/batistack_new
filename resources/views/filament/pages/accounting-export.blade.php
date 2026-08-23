<x-filament-panels::page>
    <form wire:submit="export">
        {{ $this->form }}

        <div class="mt-4 flex gap-2">
            <x-filament::button type="submit">
                Générer l'export
            </x-filament::button>

            <x-filament::button tag="a" href="#" wire:click.prevent="getPreviewData" color="gray">
                Aperçu
            </x-filament::button>
        </div>
    </form>

    @if(!empty($previewData['rows']))
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Aperçu ({{ count($previewData['rows']) }} lignes)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            @foreach($previewData['header'] as $col)
                                <th class="px-2 py-1 border-b text-left">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($previewData['rows'], 0, 50) as $row)
                            <tr>
                                @foreach($row as $val)
                                    <td class="px-2 py-1 border-b">{{ $val }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($previewData['rows']) > 50)
                    <p class="mt-2 text-sm text-gray-500">... et {{ count($previewData['rows']) - 50 }} lignes supplémentaires</p>
                @endif
            </div>
        </div>
    @endif

    @if($exportPath)
        <div class="mt-4 p-4 bg-success-50 rounded-lg">
            <p class="text-success-700">Export généré avec succès : {{ $exportFilename }}</p>
        </div>
    @endif
</x-filament-panels::page>
