<?php

namespace App\Services\RH;

use App\Models\Core\Setting;
use App\Models\Immobilisation\AssetCategory;
use Carbon\Carbon;
use Google\Cloud\Vision\V1\AnnotateFileRequest;
use Google\Cloud\Vision\V1\BatchAnnotateFilesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\InputConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleCloudVisionOcrService implements OcrServiceInterface
{
    public function extractData(string $filePath): array
    {
        $text = $this->getText($filePath);

        if (empty($text)) {
            return [
                'amount_ttc' => null,
                'amount_ht' => null,
                'vat_amount' => null,
                'date' => null,
                'merchant' => null,
                'category' => 'Autre',
            ];
        }

        return $this->parseText($text);
    }

    public function extractAssetData(string $filePath): array
    {
        $text = $this->getText($filePath);

        if (empty($text)) {
            return [
                'purchase_price' => null,
                'purchase_date' => null,
                'merchant' => null,
                'asset_category_id' => null,
            ];
        }

        return $this->parseAssetText($text);
    }

    protected function getText(string $filePath): string
    {
        $ocrEnabled = Setting::getValue('ocr_enabled', false);
        $apiKey = Setting::getValue('google_vision_api_key');

        if (! $ocrEnabled || ! $apiKey) {
            Log::warning('OCR désactivé ou clé manquante. Extraction vide.');

            return '';
        }

        // Cache optimization based on file hash to prevent double billing for same image
        $fileHash = md5_file($filePath);
        $cacheKey = 'ocr_text_'.$fileHash;

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($apiKey, $filePath) {
            try {
                $clientConfig = [];

                if (str_starts_with(trim($apiKey), '{')) {
                    $clientConfig['credentials'] = json_decode($apiKey, true);
                } else {
                    $clientConfig['credentials'] = $apiKey;
                }

                $imageAnnotator = $this->createClient($clientConfig);
                $fileContent = file_get_contents($filePath);
                $mimeType = mime_content_type($filePath);
                $text = '';

                if ($mimeType === 'application/pdf') {
                    // PDF Document Text Detection
                    $feature = (new Feature)->setType(Type::DOCUMENT_TEXT_DETECTION);
                    $inputConfig = (new InputConfig)
                        ->setMimeType('application/pdf')
                        ->setContent($fileContent);

                    $request = (new AnnotateFileRequest)
                        ->setInputConfig($inputConfig)
                        ->setFeatures([$feature])
                        ->setPages([1, 2, 3, 4, 5]);

                    $batchRequest = (new BatchAnnotateFilesRequest)
                        ->setRequests([$request]);

                    $response = $imageAnnotator->batchAnnotateFiles($batchRequest);
                    $responses = $response->getResponses();

                    if (count($responses) > 0) {
                        $fileResponse = $responses[0];
                        foreach ($fileResponse->getResponses() as $pageResponse) {
                            if ($pageResponse->getFullTextAnnotation()) {
                                $text .= $pageResponse->getFullTextAnnotation()->getText()."\n";
                            }
                        }
                    }
                } else {
                    // Standard Image Text Detection
                    $response = $imageAnnotator->documentTextDetection($fileContent);
                    $annotation = $response->getFullTextAnnotation();
                    $text = $annotation ? $annotation->getText() : '';
                }

                $imageAnnotator->close();

                return $text;
            } catch (\Exception $e) {
                Log::error('OCR Error: '.$e->getMessage());

                return '';
            }
        });
    }

    protected function createClient(array $clientConfig)
    {
        return new ImageAnnotatorClient($clientConfig);
    }

    private function parseText(string $text): array
    {
        $lines = explode("\n", $text);

        $merchant = $lines[0] ?? null;

        preg_match('/\b(\d{2}[\/\.-]\d{2}[\/\.-]\d{4})\b/', $text, $dateMatches);
        $date = null;
        if (! empty($dateMatches[1])) {
            try {
                $date = Carbon::parse(str_replace(['.', '-'], '/', $dateMatches[1]))->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        preg_match_all('/\b\d+[.,]\d{2}\b/', $text, $amountMatches);
        $amounts = [];
        if (! empty($amountMatches[0])) {
            foreach ($amountMatches[0] as $match) {
                $amounts[] = (float) str_replace(',', '.', $match);
            }
        }

        rsort($amounts);
        $amount_ttc = $amounts[0] ?? null;
        $amount_ht = null;
        $vat_amount = null;

        foreach ($amounts as $amount) {
            if ($amount_ttc && $amount < $amount_ttc) {
                $calcVat = round($amount_ttc - $amount, 2);
                if (in_array($calcVat, $amounts)) {
                    $amount_ht = $amount;
                    $vat_amount = $calcVat;
                    break;
                }
            }
        }

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

    private function parseAssetText(string $text): array
    {
        $baseData = $this->parseText($text);

        // On assets, we usually want the HT price as purchase_price
        $purchasePrice = $baseData['amount_ht'] ?? $baseData['amount_ttc'];

        // Try to match Asset Categories from the database
        $categoryId = null;
        try {
            $categories = AssetCategory::all();
            $textUpper = strtoupper($text);
            $words = preg_split('/\W+/', $textUpper);

            foreach ($categories as $cat) {
                // simple heuristic: if category name words match the text
                $catNameUpper = strtoupper($cat->name);
                $catWords = array_filter(preg_split('/\W+/', $catNameUpper), fn ($w) => strlen($w) > 3);

                if (! empty($catWords) && count(array_intersect($catWords, $words)) > 0) {
                    $categoryId = $cat->id;
                    break;
                }
            }
        } catch (\Exception $e) {
            // fallback if DB error
        }

        return [
            'purchase_price' => $purchasePrice,
            'purchase_date' => $baseData['date'],
            'merchant' => $baseData['merchant'],
            'asset_category_id' => $categoryId,
        ];
    }
}
