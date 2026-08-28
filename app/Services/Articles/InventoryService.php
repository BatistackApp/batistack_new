<?php

namespace App\Services\Articles;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
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
                ->lockForUpdate()
                ->first();

            if ($foundQuantity < 0) {
                throw new ArticlesModuleException('La quantité trouvée ne peut pas être négative.', 400);
            }

            if ($stock && $foundQuantity < $stock->reserved_quantity) {
                throw new ArticlesModuleException(
                    "La quantité trouvée ({$foundQuantity}) ne peut pas être inférieure à la quantité réservée ({$stock->reserved_quantity}).",
                    400
                );
            }

            $theoreticalQuantity = $stock ? $stock->quantity : 0;
            $adjustment = $foundQuantity - $theoreticalQuantity;

            if ($adjustment == 0) {
                return;
            }

            // Mise à jour du stock
            if (! $stock) {
                $stock = Stock::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $foundQuantity,
                ]);
            } else {
                $stock->quantity = $foundQuantity;
                $stock->save();
            }

            // Création du mouvement de stock
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id() ?? 1,
                'type' => $adjustment > 0 ? StockMouvementType::IN : StockMouvementType::OUT,
                'quantity_before' => $theoreticalQuantity,
                'quantity_delta' => $adjustment,
                'quantity_after' => $foundQuantity,
                'description' => $reason,
                'reference_type' => StockMouvementSource::INVENTORY,
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

        $documentService = app(DocumentService::class);

        $data = [
            'company' => Company::first(),
            'stocks' => $stocks,
            'totalValue' => $totalValue,
            'title' => 'RAPPORT DE VALORISATION D\'INVENTAIRE',
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $documentService->generate(
            'pdf.articles.inventory_valuation',
            $data,
            'valorisation_inventaire_'.now()->format('YmdHis'),
            'articles/inventory'
        );
    }

    private function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $value = str_replace('"', '""', $value);

        return '"'.$value.'"';
    }
}
