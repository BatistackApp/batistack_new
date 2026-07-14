<?php

use App\Services\RH\GoogleCloudVisionOcrService;
use Illuminate\Support\Facades\Log;

it('returns mock data when credentials are not set', function () {
    // Ensure env is unset for test
    putenv('GOOGLE_CLOUD_PROJECT=');
    putenv('GOOGLE_APPLICATION_CREDENTIALS=');

    $service = new GoogleCloudVisionOcrService();
    $data = $service->extractData('dummy_path.jpg');

    expect($data)->toBeArray()
        ->and($data['amount_ttc'])->toBe(24.50)
        ->and($data['amount_ht'])->toBe(20.42)
        ->and($data['merchant'])->toBe('Bricoman (Mock OCR)');
});

it('gracefully handles missing image with mock if no creds', function () {
    $service = new GoogleCloudVisionOcrService();
    $data = $service->extractData('non_existent.jpg');
    
    expect($data)->toBeArray()
        ->and($data['amount_ttc'])->not->toBeNull();
});
