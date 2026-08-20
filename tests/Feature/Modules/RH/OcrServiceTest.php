<?php

use App\Models\Core\Setting;
use App\Models\Immobilisation\AssetCategory;
use App\Services\RH\GoogleCloudVisionOcrService;

class FakeOcrParseService extends GoogleCloudVisionOcrService
{
    public ?string $forcedText = null;

    protected function getText(string $filePath): string
    {
        return $this->forcedText ?? '';
    }
}

class FakeOcrClientService extends GoogleCloudVisionOcrService
{
    public mixed $client = null;

    protected function createClient(array $clientConfig)
    {
        return $this->client;
    }
}

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

it('parses receipt fields from extracted text', function () {
    $service = new FakeOcrParseService;
    $service->forcedText = "BRICOMAN\n24,50\n20,42\n4,08\n15/06/2023\nOutillage";

    $data = $service->extractData('dummy.jpg');

    expect($data['amount_ttc'])->toBe(24.5)
        ->and($data['amount_ht'])->toBe(20.42)
        ->and($data['vat_amount'])->toBe(4.08)
        ->and($data['merchant'])->toBe('BRICOMAN');
});

it('categorizes extracted text by merchant keywords', function (string $text, string $expected) {
    $service = new FakeOcrParseService;
    $service->forcedText = $text;

    $data = $service->extractData('dummy.jpg');

    expect($data['category'])->toBe($expected);
})->with([
    ["TOTAL\n40,00\n", 'Carburant'],
    ["VINCI\n10,00\n", 'Péage'],
    ["INDIGO\n5,00\n", 'Parking'],
    ["MCDONALD\n15,00\n", 'Repas'],
    ["EPICERIE\n12,50\n", 'Autre'],
]);

it('extracts asset data and matches an asset category', function () {
    $category = AssetCategory::factory()->create(['name' => 'Outillage']);

    $service = new FakeOcrParseService;
    $service->forcedText = "BRICOMAN\n24,50\n20,42\n4,08\n15/06/2023\nOutillage";

    $data = $service->extractAssetData('dummy.jpg');

    expect($data['purchase_price'])->toBe(20.42)
        ->and($data['merchant'])->toBe('BRICOMAN')
        ->and($data['asset_category_id'])->toBe($category->id);
});

it('returns the text extracted by the Vision client when OCR is enabled', function () {
    Setting::setValue('ocr_enabled', '1');
    Setting::setValue('google_vision_api_key', 'fake-key');

    $annotation = Mockery::mock();
    $annotation->shouldReceive('getText')->andReturn("EPICERIE\n12,50\n");

    $response = Mockery::mock();
    $response->shouldReceive('getFullTextAnnotation')->andReturn($annotation);

    $client = Mockery::mock();
    $client->shouldReceive('documentTextDetection')->andReturn($response);
    $client->shouldReceive('close');

    $service = new FakeOcrClientService;
    $service->client = $client;

    $temp = tempnam(sys_get_temp_dir(), 'ocr').'.jpg';
    file_put_contents($temp, 'fake image bytes');

    $data = $service->extractData($temp);

    expect($data['merchant'])->toBe('EPICERIE');

    unlink($temp);
});

it('returns an empty extraction when the Vision client throws', function () {
    Setting::setValue('ocr_enabled', '1');
    Setting::setValue('google_vision_api_key', 'fake-key');

    $client = Mockery::mock();
    $client->shouldReceive('documentTextDetection')->andThrow(new Exception('Vision API failure'));
    $client->shouldReceive('close');

    $service = new FakeOcrClientService;
    $service->client = $client;

    $temp = tempnam(sys_get_temp_dir(), 'ocr').'.jpg';
    file_put_contents($temp, 'fake image bytes');

    $data = $service->extractData($temp);

    expect($data['amount_ttc'])->toBeNull();

    unlink($temp);
});
