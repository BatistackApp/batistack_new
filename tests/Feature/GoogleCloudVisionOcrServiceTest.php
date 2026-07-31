<?php

use App\Services\RH\GoogleCloudVisionOcrService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\BatchAnnotateFilesResponse;
use Google\Cloud\Vision\V1\AnnotateFileResponse;
use Google\Cloud\Vision\V1\AnnotateImageResponse;
use Google\Cloud\Vision\V1\TextAnnotation;

class TestableOcrService extends GoogleCloudVisionOcrService
{
    public $mockClient;

    protected function createClient(array $clientConfig)
    {
        return $this->mockClient;
    }
}

test('extractData handles multi-page PDF using batchAnnotateFiles', function () {
    // 1. Setup mock Settings to enable OCR
    \App\Models\Core\Setting::factory()->create(['key' => 'ocr_enabled', 'value' => '1', 'type' => 'boolean']);
    \App\Models\Core\Setting::factory()->create(['key' => 'google_vision_api_key', 'value' => 'fake-key', 'type' => 'text']);

    // 2. Create a fake PDF file
    Storage::fake('local');
    Storage::disk('local')->put('test.pdf', '%PDF-1.4 Fake PDF Content');
    $filePath = Storage::disk('local')->path('test.pdf');

    // 3. Mock the deep Google API response objects
    $mockTextAnnotation1 = Mockery::mock(TextAnnotation::class);
    $mockTextAnnotation1->shouldReceive('getText')->andReturn("TOTAL\n24,50\n");

    $mockTextAnnotation2 = Mockery::mock(TextAnnotation::class);
    $mockTextAnnotation2->shouldReceive('getText')->andReturn("20,42\n4,08\n12/06/2023\nCarburant");

    $mockImageResponse1 = Mockery::mock(AnnotateImageResponse::class);
    $mockImageResponse1->shouldReceive('getFullTextAnnotation')->andReturn($mockTextAnnotation1);

    $mockImageResponse2 = Mockery::mock(AnnotateImageResponse::class);
    $mockImageResponse2->shouldReceive('getFullTextAnnotation')->andReturn($mockTextAnnotation2);

    $mockFileResponse = Mockery::mock(AnnotateFileResponse::class);
    $mockFileResponse->shouldReceive('getResponses')->andReturn([$mockImageResponse1, $mockImageResponse2]);

    $mockBatchResponse = Mockery::mock(BatchAnnotateFilesResponse::class);
    $mockBatchResponse->shouldReceive('getResponses')->andReturn([$mockFileResponse]);

    // 4. Use an anonymous class to bypass final class Mockery limitations
    $mockClient = new class($mockBatchResponse) {
        private $response;
        public function __construct($response) { $this->response = $response; }
        public function batchAnnotateFiles($request) { return $this->response; }
        public function close() {}
    };

    // 5. Inject client into service
    $service = new TestableOcrService();
    $service->mockClient = $mockClient;

    // 6. Clear cache to force OCR
    Cache::clear();

    // 7. Execute
    $data = $service->extractData($filePath);

    // 8. Assertions based on the concatenated text (Page 1 + Page 2)
    // The text will be: "TOTAL\n24,50\n20,42\n4,08\n15/06/2023\nCarburant\n"
    expect($data)->toBeArray();
    expect($data['merchant'])->toBe('TOTAL');
    expect($data['amount_ttc'])->toEqual(24.50);
    expect($data['date'])->toBe('2023-12-06');
    expect($data['category'])->toBe('Carburant');
});

test('extractData handles image using documentTextDetection', function () {
    \App\Models\Core\Setting::factory()->create(['key' => 'ocr_enabled', 'value' => '1', 'type' => 'boolean']);
    \App\Models\Core\Setting::factory()->create(['key' => 'google_vision_api_key', 'value' => 'fake-key', 'type' => 'text']);

    // Create a fake JPG file
    Storage::fake('local');
    Storage::disk('local')->put('test.jpg', 'Fake JPG Content');
    $filePath = Storage::disk('local')->path('test.jpg');

    $mockTextAnnotation = Mockery::mock(TextAnnotation::class);
    $mockTextAnnotation->shouldReceive('getText')->andReturn("RESTAURANT\n15,00\n12/12/2023");

    $mockResponse = Mockery::mock(AnnotateImageResponse::class);
    $mockResponse->shouldReceive('getFullTextAnnotation')->andReturn($mockTextAnnotation);

    $mockClient = new class($mockResponse) {
        private $response;
        public function __construct($response) { $this->response = $response; }
        public function documentTextDetection($request) { return $this->response; }
        public function close() {}
    };

    $service = new TestableOcrService();
    $service->mockClient = $mockClient;

    Cache::clear();

    $data = $service->extractData($filePath);

    expect($data)->toBeArray();
    expect($data['merchant'])->toBe('RESTAURANT');
    expect($data['amount_ttc'])->toEqual(15.00);
    expect($data['date'])->toBe('2023-12-12');
    expect($data['category'])->toBe('Repas');
});
