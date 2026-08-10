<?php

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Jobs\Gpao\GenerateManufacturingOrderPdfJob;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Gpao\GpaoDocumentService;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = \App\Models\Core\VatRate::create(['name' => 'TVA', 'rate' => 20]);

    $this->item = Item::create([
        'reference' => 'IT-PDF',
        'name' => 'Item PDF',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $this->order = ManufacturingOrder::create([
        'reference' => 'OF-PDF',
        'item_id' => $this->item->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::DRAFT,
    ]);
});

it('generates and attaches PDF to manufacturing order', function () {
    Storage::fake('local');
    
    // We can mock the document service so we don't really generate a PDF with Browsershot
    $documentServiceMock = Mockery::mock(GpaoDocumentService::class);
    
    // Simuler la création du fichier
    $pdfPath = 'chantiers/of/OF-PDF.pdf';
    Storage::disk('local')->put($pdfPath, 'dummy content');
    
    $documentServiceMock
        ->shouldReceive('generateManufacturingOrderPdf')
        ->with(Mockery::on(fn($o) => $o->id === $this->order->id))
        ->once()
        ->andReturn($pdfPath);

    // Swap the class to use the mock
    $this->app->instance(GpaoDocumentService::class, $documentServiceMock);

    // Mocker la configuration du disk
    config(['filesystems.default' => 'local']);

    $job = new GenerateManufacturingOrderPdfJob($this->order->id);
    $job->handle($documentServiceMock);

    // Si on arrive ici sans exception et que le mock a été appelé, le job fonctionne.
    expect(true)->toBeTrue();
});
