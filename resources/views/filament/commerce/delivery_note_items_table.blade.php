<table class="min-w-full divide-y divide-gray-300">
    <thead>
    <tr>
        <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Référence</th>
        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Désignation</th>
        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Qte Commandé</th>
        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Qte Livré / Réceptionner</th>
    </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        @foreach($getState() as $deliveryItem)
            @php
                $orderedQuantity = 0;
                $orderItem = $deliveryItem->customerDeliveryNote->order->items->firstWhere('item_id', $deliveryItem->item_id);
                if ($orderItem) {
                        $orderedQuantity = number_format($orderItem->quantity, 2, ',', ' ');
                    }
            @endphp
            <tr>
                <td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0">{{ $deliveryItem->item->reference }}</td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $deliveryItem->item->name }}</td>
                <td class="px-3 py-4 text-sm text-center whitespace-nowrap text-gray-500">{{ $orderedQuantity }}</td>
                <td class="px-3 py-4 text-sm text-center whitespace-nowrap text-gray-500">{{ \Illuminate\Support\Number::format($deliveryItem->quantity_delivered) }}</td>
            </tr>
        @endforeach

    <!-- More people... -->
    </tbody>
</table>
