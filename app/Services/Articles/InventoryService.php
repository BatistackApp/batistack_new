<?php

namespace App\Services\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryService
{
    /**
     * Effectue une régularisation de stock (Inventaire).
     *
     * @param  float  $foundQuantity  La quantité réellement comptée.
     *
     * @throws Throwable
     */
    public function reconcile(Item $item, Warehouse $warehouse, float $foundQuantity, string $reason): void
    {
        DB::transaction(function () use ($item, $warehouse, $foundQuantity, $reason) {
            $stock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            $theoreticalQuantity = $stock ? $stock->quantity : 0;
            $adjustment = $foundQuantity - $theoreticalQuantity;

            if ($adjustment == 0) {
                return;
            }

            // Mise à jour du stock
            $stock = Stock::updateOrCreate(
                ['item_id' => $item->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => $foundQuantity]
            );

            // Création du mouvement de stock
            \App\Models\Articles\StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id() ?? 1,
                'type' => $adjustment > 0 ? \App\Enums\Articles\StockMouvementType::IN : \App\Enums\Articles\StockMouvementType::OUT,
                'quantity_before' => $theoreticalQuantity,
                'quantity_delta' => $adjustment,
                'quantity_after' => $foundQuantity,
                'description' => $reason,
                'reference_type' => \App\Enums\Articles\StockMouvementSource::INVENTORY,
                'reference_id' => null,
            ]);
        });
    }

    /**
     * Génère un export CSV de la valorisation de l'inventaire.
     */
    public function generateValuationCsv(): string
    {
        $stocks = Stock::with(['item', 'warehouse'])->where('quantity', '>', 0)->get();

        $csv = "Reference;Designation;Depot;Quantite;Prix Unitaire (HT);Valeur Totale (HT)\n";

        foreach ($stocks as $stock) {
            $unitPrice = $stock->item->purchase_price ?? 0;
            $totalValue = $stock->quantity * $unitPrice;

            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s\n",
                $this->escapeCsv($stock->item->reference),
                $this->escapeCsv($stock->item->name),
                $this->escapeCsv($stock->warehouse->name),
                number_format($stock->quantity, 2, '.', ''),
                number_format($unitPrice, 2, '.', ''),
                number_format($totalValue, 2, '.', '')
            );
        }

        return $csv;
    }

    /**
     * Génère un export PDF officiel de la valorisation de l'inventaire.
     */
    public function generateValuationPdf(): string
    {
        $stocks = Stock::with(['item', 'warehouse'])->where('quantity', '>', 0)->get();
        
        $totalValue = $stocks->sum(function ($stock) {
            return $stock->quantity * ($stock->item->purchase_price ?? 0);
        });

        $documentService = app(\App\Services\Core\DocumentService::class);

        $data = [
            'company' => \App\Models\Core\Company::first(),
            'stocks' => $stocks,
            'totalValue' => $totalValue,
            'title' => 'RAPPORT DE VALORISATION D\'INVENTAIRE',
            'generated_at' => \Carbon\Carbon::now()->format('d/m/Y H:i'),
        ];

        return $documentService->generate(
            'pdf.articles.inventory_valuation',
            $data,
            'valorisation_inventaire_' . now()->format('YmdHis'),
            'articles/inventory'
        );
    }

    private function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $value = str_replace('"', '""', $value);
        return '"' . $value . '"';
    }
}
