<?php

use App\Services\RH\GoogleCloudVisionOcrService;

it('returns an empty extraction when credentials are not set', function () {
    // Ensure env is unset for test
    putenv('GOOGLE_CLOUD_PROJECT=');
    putenv('GOOGLE_APPLICATION_CREDENTIALS=');

    $service = new GoogleCloudVisionOcrService;
    $data = $service->extractData('dummy_path.jpg');

    expect($data)->toBeArray()
        ->and($data['amount_ttc'])->toBeNull()
        ->and($data['amount_ht'])->toBeNull()
        ->and($data['merchant'])->toBeNull();
});

it('gracefully handles a missing image without producing fabricated data', function () {
    $service = new GoogleCloudVisionOcrService;
    $data = $service->extractData('non_existent.jpg');

    expect($data)->toBeArray()
        ->and($data['amount_ttc'])->toBeNull();
});

it('returns an empty asset extraction when credentials are not set', function () {
    $service = new GoogleCloudVisionOcrService;
    // Crée un fichier bidon pour avoir un md5
    file_put_contents('dummy_asset.jpg', 'fake image content');

    $data = $service->extractAssetData('dummy_asset.jpg');

    expect($data)->toBeArray()
        ->and($data['purchase_price'])->toBeNull()
        ->and($data['merchant'])->toBeNull();

    unlink('dummy_asset.jpg');
});
