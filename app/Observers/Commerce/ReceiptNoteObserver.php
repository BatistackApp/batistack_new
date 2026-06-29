<?php

namespace App\Observers\Commerce;

use App\Jobs\CalculateSupplierScore;
use App\Models\Commerce\ReceiptNote;

class ReceiptNoteObserver
{
    /**
     * Handle the ReceiptNote "created" event.
     */
    public function created(ReceiptNote $receiptNote): void
    {
        $this->updateSupplierScore($receiptNote);
    }

    /**
     * Handle the ReceiptNote "updated" event.
     */
    public function updated(ReceiptNote $receiptNote): void
    {
        $this->updateSupplierScore($receiptNote);
    }

    /**
     * Handle the ReceiptNote "deleted" event.
     */
    public function deleted(ReceiptNote $receiptNote): void
    {
        $this->updateSupplierScore($receiptNote);
    }

    /**
     * Handle the ReceiptNote "restored" event.
     */
    public function restored(ReceiptNote $receiptNote): void
    {
        $this->updateSupplierScore($receiptNote);
    }

    /**
     * Handle the ReceiptNote "force deleted" event.
     */
    public function forceDeleted(ReceiptNote $receiptNote): void
    {
        $this->updateSupplierScore($receiptNote);
    }

    private function updateSupplierScore(ReceiptNote $receiptNote): void
    {
        // On s'assure d'avoir la commande et le fournisseur
        if ($receiptNote->order && $receiptNote->order->supplier) {
            CalculateSupplierScore::dispatch($receiptNote->order->supplier);
        }
    }
}
