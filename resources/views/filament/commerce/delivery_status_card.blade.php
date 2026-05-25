

<div class="overflow-hidden rounded-lg bg-{{ $getRecord()->status->getColor() }} text-white shadow-sm">
    <div class="flex flex-col p-6">
        <div class="flex flex-row align-middle">
            @svg(\ToneGabes\Filament\Icons\Enums\Phosphor::Truck->getLabel(), ['style' => 'width: 24px; height: 24px; margin-right: 5px'])
            <span class="text-white text-lg">Etat de la commande</span>
        </div>
        <div class="text-2xl font-black text-center pt-5">{{ $getRecord()->status->getLabel() }}</div>
    </div>
</div>
