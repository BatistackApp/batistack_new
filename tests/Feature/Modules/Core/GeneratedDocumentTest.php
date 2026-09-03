<?php

use App\Models\Core\GeneratedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a generated document', function () {
    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'devis',
        'file_name' => 'Devis DEV-2026-001',
        'file_path' => 'documents/commerce/devis/devis_DEV-2026-001.pdf',
        'file_disk' => 'public',
        'file_size' => 123456,
        'generated_at' => now(),
    ]);

    expect($document->module)->toBe('commerce')
        ->and($document->type)->toBe('devis')
        ->and($document->file_name)->toBe('Devis DEV-2026-001')
        ->and($document->file_path)->toBe('documents/commerce/devis/devis_DEV-2026-001.pdf')
        ->and($document->file_disk)->toBe('public')
        ->and($document->file_size)->toBe(123456)
        ->and($document->generated_at)->not->toBeNull();
});

it('has correct module labels', function () {
    $document = GeneratedDocument::factory()->create(['module' => 'commerce']);
    expect($document->module_label)->toBe('Commerce');

    $document = GeneratedDocument::factory()->create(['module' => 'rh']);
    expect($document->module_label)->toBe('Ressources Humaines');

    $document = GeneratedDocument::factory()->create(['module' => 'chantiers']);
    expect($document->module_label)->toBe('Chantiers');

    $document = GeneratedDocument::factory()->create(['module' => 'tiers']);
    expect($document->module_label)->toBe('Tiers');
});

it('has correct module colors', function () {
    $document = GeneratedDocument::factory()->create(['module' => 'commerce']);
    expect($document->module_color)->toBe('info');

    $document = GeneratedDocument::factory()->create(['module' => 'rh']);
    expect($document->module_color)->toBe('success');

    $document = GeneratedDocument::factory()->create(['module' => 'chantiers']);
    expect($document->module_color)->toBe('warning');
});

it('formats file size correctly', function () {
    $document = GeneratedDocument::factory()->create(['file_size' => 1024]);
    expect($document->formatted_size)->toBe('1 Ko');

    $document = GeneratedDocument::factory()->create(['file_size' => 1048576]);
    expect($document->formatted_size)->toBe('1 Mo');

    $document = GeneratedDocument::factory()->create(['file_size' => null]);
    expect($document->formatted_size)->toBe('—');
});

it('can scope by module', function () {
    GeneratedDocument::factory()->create(['module' => 'commerce']);
    GeneratedDocument::factory()->create(['module' => 'commerce']);
    GeneratedDocument::factory()->create(['module' => 'rh']);

    $commerceDocs = GeneratedDocument::byModule('commerce')->count();
    $rhDocs = GeneratedDocument::byModule('rh')->count();

    expect($commerceDocs)->toBe(2)
        ->and($rhDocs)->toBe(1);
});

it('can scope by type', function () {
    GeneratedDocument::factory()->create(['type' => 'devis']);
    GeneratedDocument::factory()->create(['type' => 'devis']);
    GeneratedDocument::factory()->create(['type' => 'facture']);

    $devisDocs = GeneratedDocument::byType('devis')->count();
    $factureDocs = GeneratedDocument::byType('facture')->count();

    expect($devisDocs)->toBe(2)
        ->and($factureDocs)->toBe(1);
});

it('can scope by entity', function () {
    GeneratedDocument::factory()->create([
        'entity_type' => 'App\Models\Commerce\CustomerQuote',
        'entity_id' => 1,
    ]);
    GeneratedDocument::factory()->create([
        'entity_type' => 'App\Models\Commerce\CustomerQuote',
        'entity_id' => 1,
    ]);
    GeneratedDocument::factory()->create([
        'entity_type' => 'App\Models\Commerce\CustomerInvoice',
        'entity_id' => 1,
    ]);

    $quoteDocs = GeneratedDocument::forEntity('App\Models\Commerce\CustomerQuote', 1)->count();
    $invoiceDocs = GeneratedDocument::forEntity('App\Models\Commerce\CustomerInvoice', 1)->count();

    expect($quoteDocs)->toBe(2)
        ->and($invoiceDocs)->toBe(1);
});

it('can search documents', function () {
    GeneratedDocument::factory()->create(['file_name' => 'Devis DEV-2026-001']);
    GeneratedDocument::factory()->create(['file_name' => 'Facture FAC-2026-001']);
    GeneratedDocument::factory()->create(['file_name' => 'Bon de commande BC-2026-001']);

    $results = GeneratedDocument::search('DEV')->count();
    expect($results)->toBe(1);

    $results = GeneratedDocument::search('2026')->count();
    expect($results)->toBe(3);
});

it('soft deletes documents', function () {
    $document = GeneratedDocument::factory()->create();

    $document->delete();

    expect(GeneratedDocument::find($document->id))->toBeNull()
        ->and(GeneratedDocument::withTrashed()->find($document->id))->not->toBeNull();
});
