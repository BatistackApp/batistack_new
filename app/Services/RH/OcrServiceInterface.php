<?php

namespace App\Services\RH;

interface OcrServiceInterface
{
    /**
     * Parse a receipt or invoice image and extract relevant data.
     * 
     * @param string $filePath The absolute path to the image file
     * @return array{amount_ttc: ?float, amount_ht: ?float, vat_amount: ?float, date: ?string, merchant: ?string}
     */
    public function extractData(string $filePath): array;

    /**
     * Parse an asset invoice image and extract relevant data for fixed assets.
     * 
     * @param string $filePath The absolute path to the image file
     * @return array{purchase_price: ?float, purchase_date: ?string, merchant: ?string, asset_category_id: ?int}
     */
    public function extractAssetData(string $filePath): array;
}
