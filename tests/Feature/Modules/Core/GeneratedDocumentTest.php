<?php

use App\Models\Core\GeneratedDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

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

it('belongs to a user via generatedBy relation', function () {
    $user = User::factory()->create();
    $document = GeneratedDocument::factory()->create(['generated_by' => $user->id]);

    expect($document->generatedBy)->toBeInstanceOf(User::class)
        ->and($document->generatedBy->id)->toBe($user->id);
});

it('returns null for generatedBy when no user', function () {
    $document = GeneratedDocument::factory()->create(['generated_by' => null]);

    expect($document->generatedBy)->toBeNull();
});

it('returns temporary url for s3 disk', function () {
    Storage::fake('s3');

    $document = GeneratedDocument::factory()->create([
        'file_path' => 'documents/test.pdf',
        'file_disk' => 's3',
    ]);

    $url = $document->temporaryUrl();
    expect($url)->toContain('https://');
});

it('returns regular url for non-s3 disk', function () {
    $document = GeneratedDocument::factory()->create([
        'file_path' => 'documents/test.pdf',
        'file_disk' => 'public',
    ]);

    $url = $document->temporaryUrl();
    expect($url)->toContain('documents/test.pdf');
});

it('returns null temporaryUrl when file_path is null', function () {
    $document = new GeneratedDocument([
        'file_path' => null,
        'file_disk' => 'public',
    ]);

    expect($document->temporaryUrl())->toBeNull();
});

it('deletes file from storage when deleteFile is called', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/test');
    Storage::disk('public')->put('documents/test/file.pdf', 'content');

    $document = GeneratedDocument::factory()->create([
        'file_path' => 'documents/test/file.pdf',
        'file_disk' => 'public',
    ]);

    $document->deleteFile();

    Storage::disk('public')->assertMissing('documents/test/file.pdf');
    expect(GeneratedDocument::withTrashed()->find($document->id))->not->toBeNull();
});

it('deletes record even if file does not exist', function () {
    Storage::fake('public');

    $document = GeneratedDocument::factory()->create([
        'file_path' => 'documents/nonexistent.pdf',
        'file_disk' => 'public',
    ]);

    $result = $document->deleteFile();

    expect($result)->toBeTrue()
        ->and(GeneratedDocument::withTrashed()->find($document->id))->not->toBeNull();
});

it('has correct module labels for all modules', function () {
    $modules = [
        'gpao' => 'GPAO',
        'flottes' => 'Flottes',
        'immobilisations' => 'Immobilisations',
        'interventions' => 'Interventions',
        'articles' => 'Articles',
    ];

    foreach ($modules as $module => $label) {
        $document = GeneratedDocument::factory()->create(['module' => $module]);
        expect($document->module_label)->toBe($label);
    }
});

it('has default module label for unknown module', function () {
    $document = GeneratedDocument::factory()->create(['module' => 'custom_module']);
    expect($document->module_label)->toBe('Custom_module');
});

it('has correct module colors for all modules', function () {
    $modules = [
        'tiers' => 'primary',
        'gpao' => 'gray',
        'flottes' => 'danger',
        'immobilisations' => 'success',
        'interventions' => 'warning',
        'articles' => 'info',
    ];

    foreach ($modules as $module => $color) {
        $document = GeneratedDocument::factory()->create(['module' => $module]);
        expect($document->module_color)->toBe($color);
    }
});

it('has default module color for unknown module', function () {
    $document = GeneratedDocument::factory()->create(['module' => 'custom_module']);
    expect($document->module_color)->toBe('gray');
});

it('formats file size in bytes correctly', function () {
    $document = GeneratedDocument::factory()->create(['file_size' => 500]);
    expect($document->formatted_size)->toBe('500 o');
});

it('formats file size in gigabytes correctly', function () {
    $document = GeneratedDocument::factory()->create(['file_size' => 1073741824]);
    expect($document->formatted_size)->toBe('1 Go');
});
