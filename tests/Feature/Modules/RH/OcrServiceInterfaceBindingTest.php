<?php

use App\Services\RH\GoogleCloudVisionOcrService;
use App\Services\RH\OcrServiceInterface;

it('resolves the OCR interface to the concrete Google service', function () {
    $ocr = app(OcrServiceInterface::class);

    expect($ocr)->toBeInstanceOf(GoogleCloudVisionOcrService::class);
});

it('exposes the asset OCR method through the container', function () {
    $ocr = app(OcrServiceInterface::class);

    expect(method_exists($ocr, 'extractAssetData'))->toBeTrue()
        ->and(method_exists($ocr, 'extractData'))->toBeTrue();
});
