<?php

namespace App\Services\RH;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Illuminate\Support\Facades\Log;

class GoogleCloudVisionOcrService implements OcrServiceInterface
{
    public function extractData(string $filePath): array
    {
        $ocrEnabled = \App\Models\Core\Setting::getValue('ocr_enabled', false);
        $apiKey = \App\Models\Core\Setting::getValue('google_vision_api_key');
        
        // For local development or if disabled, return mock data
        if (!$ocrEnabled || !$apiKey) {
            Log::warning('OCR désactivé ou clé manquante. Retour des données simulées (Mock).');
            return [
                'amount_ttc' => 24.50,
                'amount_ht' => 20.42,
                'vat_amount' => 4.08,
                'date' => date('Y-m-d'),
                'merchant' => 'Bricoman (Mock OCR)',
                'category' => 'Carburant', // Mock as fuel to test the vehicle relationship
            ];
        }

        try {
            $clientConfig = [];
            
            // If the stored setting looks like a JSON string (Service Account), pass it as credentials
            if (str_starts_with(trim($apiKey), '{')) {
                $clientConfig['credentials'] = json_decode($apiKey, true);
            } else {
                // Otherwise fallback to whatever is in the env, or try to use it as a path
                $clientConfig['credentials'] = $apiKey;
            }

            $imageAnnotator = new ImageAnnotatorClient($clientConfig);
            $image = file_get_contents($filePath);
            $response = $imageAnnotator->documentTextDetection($image);
            $annotation = $response->getFullTextAnnotation();

            $text = $annotation ? $annotation->getText() : '';
            $imageAnnotator->close();

            return $this->parseText($text);
        } catch (\Exception $e) {
            Log::error('OCR Error: ' . $e->getMessage());
            return [
                'amount_ttc' => null,
                'amount_ht' => null,
                'vat_amount' => null,
                'date' => null,
                'merchant' => null,
            ];
        }
    }

    private function parseText(string $text): array
    {
        $lines = explode("\n", $text);

        $merchant = $lines[0] ?? null; // Usually the first line is the merchant name

        // Find Date (DD/MM/YYYY or DD-MM-YYYY)
        preg_match('/\b(\d{2}[\/\.-]\d{2}[\/\.-]\d{4})\b/', $text, $dateMatches);
        $date = null;
        if (!empty($dateMatches[1])) {
            try {
                $date = \Carbon\Carbon::parse(str_replace(['.', '-'], '/', $dateMatches[1]))->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        // Find amounts
        preg_match_all('/\b\d+[.,]\d{2}\b/', $text, $amountMatches);
        $amounts = [];
        if (!empty($amountMatches[0])) {
            foreach ($amountMatches[0] as $match) {
                $amounts[] = (float) str_replace(',', '.', $match);
            }
        }

        rsort($amounts); // Sort descending to assume biggest is TTC
        $amount_ttc = $amounts[0] ?? null;
        $amount_ht = null;
        $vat_amount = null;

        // Try to deduce HT and VAT by looking for valid subtraction
        foreach ($amounts as $amount) {
            if ($amount_ttc && $amount < $amount_ttc) {
                $calcVat = round($amount_ttc - $amount, 2);
                // If the calculated VAT is also present in the text, it's highly likely to be correct
                if (in_array($calcVat, $amounts)) {
                    $amount_ht = $amount;
                    $vat_amount = $calcVat;
                    break;
                }
            }
        }

        // Heuristic to guess the category based on text content
        $textUpper = strtoupper($text);
        $category = 'Autre';
        
        $fuelKeywords = ['CARBURANT', 'ESSENCE', 'GASOIL', 'DIESEL', 'SP95', 'SP98', 'TOTAL', 'ESSO', 'SHELL', 'AVIA', 'STATION'];
        $tollKeywords = ['PEAGE', 'PÉAGE', 'AUTOROUTE', 'VINCI', 'SANEF', 'APRR', 'AREA'];
        $parkingKeywords = ['PARKING', 'STATIONNEMENT', 'INDIGO', 'Q-PARK', 'EFFIA', 'HORODATEUR'];
        $mealKeywords = ['RESTAURANT', 'REPAS', 'BRASSERIE', 'MCDONALD', 'BURGER KING', 'KFC', 'BOULANGERIE'];

        if (array_intersect($fuelKeywords, preg_split('/\W+/', $textUpper))) {
            $category = 'Carburant';
        } elseif (array_intersect($tollKeywords, preg_split('/\W+/', $textUpper))) {
            $category = 'Péage';
        } elseif (array_intersect($parkingKeywords, preg_split('/\W+/', $textUpper))) {
            $category = 'Parking';
        } elseif (array_intersect($mealKeywords, preg_split('/\W+/', $textUpper))) {
            $category = 'Repas';
        }

        return [
            'amount_ttc' => $amount_ttc,
            'amount_ht' => $amount_ht,
            'vat_amount' => $vat_amount,
            'date' => $date,
            'merchant' => $merchant,
            'category' => $category,
        ];
    }
}
