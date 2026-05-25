<div class="flex flex-col bg-white p-5 rounded-lg shadow-lg">
    <div class="flex flex-row justify-between bg-blue-500 p-2 text-white">
        <span>TOTAL HT</span>
        <span>{{ \Illuminate\Support\Number::currency($getRecord()->total_ht, 'EUR', 'fr') }}</span>
    </div>
    <div class="flex flex-row justify-between bg-white p-2">
        <span>TOTAL TVA</span>
        <span>{{ \Illuminate\Support\Number::currency($getRecord()->total_tva, 'EUR', 'fr') }}</span>
    </div>
    <div class="flex flex-row justify-between bg-blue-500 p-2 text-white font-bold text-xl">
        <span>TOTAL TTC</span>
        <span>{{ \Illuminate\Support\Number::currency($getRecord()->total_ttc, 'EUR', 'fr') }}</span>
    </div>
</div>
