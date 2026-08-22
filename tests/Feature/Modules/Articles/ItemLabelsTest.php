<?php

use App\Models\Articles\Item;
use App\Services\Articles\ArticleDocumentService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function mockLabelsService(array $expectations): ArticleDocumentService
{
    $mock = \Mockery::mock(ArticleDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')
        ->once()
        ->with(...$expectations)
        ->andReturn('documents/articles/test.pdf');
    app()->instance(ArticleDocumentService::class, $mock);
    return app(ArticleDocumentService::class);
}

it('generates A4 labels for a single item', function () {
    $service = mockLabelsService([
        'documents.articles.labels_a4',
        \Mockery::on(fn ($data) => count($data['labels']) === 1 && isset($data['labels'][0]['qrCode'])),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create(['barcode' => 'BC-001', 'reference' => 'REF-001']);
    $result = $service->generateLabels(collect([$item]), 'a4', 1);

    expect($result)->toBe('documents/articles/test.pdf');
});

it('generates Dymo 28x89 labels with correct paper size', function () {
    $service = mockLabelsService([
        'documents.articles.labels_dymo_28_89',
        \Mockery::on(fn ($data) => $data['paperSize'] === ['width' => 89, 'height' => 28]
            && $data['margins'] === ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create();
    $result = $service->generateLabels(collect([$item]), 'dymo_28_89', 1);

    expect($result)->toBe('documents/articles/test.pdf');
});

it('generates Dymo 59x190 labels with correct paper size', function () {
    $service = mockLabelsService([
        'documents.articles.labels_dymo_59_190',
        \Mockery::on(fn ($data) => $data['paperSize'] === ['width' => 190, 'height' => 59]
            && $data['margins'] === ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create();
    $result = $service->generateLabels(collect([$item]), 'dymo_59_190', 1);

    expect($result)->toBe('documents/articles/test.pdf');
});

it('applies the correct number of copies per item', function () {
    $service = mockLabelsService([
        \Mockery::type('string'),
        \Mockery::on(fn ($data) => count($data['labels']) === 6),
        \Mockery::type('string'),
        'articles',
    ]);

    $items = Item::factory()->count(2)->create();
    $result = $service->generateLabels($items, 'a4', 3);

    expect($result)->toBe('documents/articles/test.pdf');
});

it('generates QR code as base64 PNG from barcode', function () {
    $service = mockLabelsService([
        \Mockery::type('string'),
        \Mockery::on(fn ($data) => str_starts_with($data['labels'][0]['qrCode'], 'data:image/png;base64,')),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create(['barcode' => 'BC-TEST-123', 'reference' => 'REF-123']);
    $service->generateLabels(collect([$item]), 'a4', 1);
});

it('falls back to reference for QR when barcode is null', function () {
    $service = mockLabelsService([
        \Mockery::type('string'),
        \Mockery::on(fn ($data) => str_starts_with($data['labels'][0]['qrCode'], 'data:image/png;base64,')),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create(['barcode' => null, 'reference' => 'REF-FALLBACK']);
    $service->generateLabels(collect([$item]), 'a4', 1);
});

it('throws exception for unsupported format', function () {
    app(ArticleDocumentService::class)->generateLabels(
        collect([Item::factory()->create()]),
        'unsupported_format',
        1
    );
})->throws(\InvalidArgumentException::class, "Format d'étiquette non supporté");

it('handles empty item collection', function () {
    $service = mockLabelsService([
        \Mockery::type('string'),
        \Mockery::on(fn ($data) => $data['labels'] === []),
        \Mockery::type('string'),
        'articles',
    ]);

    $result = $service->generateLabels(collect(), 'a4', 1);

    expect($result)->toBe('documents/articles/test.pdf');
});

it('includes item name and reference in label data', function () {
    $service = mockLabelsService([
        \Mockery::type('string'),
        \Mockery::on(fn ($data) => $data['labels'][0]['item']->name === 'Test Article'
            && $data['labels'][0]['item']->reference === 'REF-TEST'
            && $data['labels'][0]['item']->barcode === 'BC-TEST'),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create([
        'name' => 'Test Article',
        'reference' => 'REF-TEST',
        'barcode' => 'BC-TEST',
    ]);
    $service->generateLabels(collect([$item]), 'a4', 1);
});

it('uses A4 view without paper size for A4 format', function () {
    $service = mockLabelsService([
        'documents.articles.labels_a4',
        \Mockery::on(fn ($data) => !isset($data['paperSize']) && !isset($data['margins'])),
        \Mockery::type('string'),
        'articles',
    ]);

    $item = Item::factory()->create();
    $result = $service->generateLabels(collect([$item]), 'a4', 1);

    expect($result)->toBe('documents/articles/test.pdf');
});
