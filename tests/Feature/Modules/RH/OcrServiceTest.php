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
        ->and($data['merchant'])->toBe('BRICOMAN');
});

it('gracefully handles missing image with mock if no creds', function () {
    $service = new GoogleCloudVisionOcrService();
    $data = $service->extractData('non_existent.jpg');
    
    expect($data)->toBeArray()
        ->and($data['amount_ttc'])->not->toBeNull();
});

it('returns mock asset data when credentials are not set', function () {
    $service = new GoogleCloudVisionOcrService();
    // Crée un fichier bidon pour avoir un md5
    file_put_contents('dummy_asset.jpg', 'fake image content');
    
    $data = $service->extractAssetData('dummy_asset.jpg');

    expect($data)->toBeArray()
        ->and($data['purchase_price'])->toBe(20.42) // Since amount_ht is 20.42 in mock text
        ->and($data['merchant'])->toBe('BRICOMAN');
        
    unlink('dummy_asset.jpg');
});
