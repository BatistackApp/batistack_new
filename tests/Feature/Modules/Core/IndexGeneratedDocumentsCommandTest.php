<?php

use App\Console\Commands\Core\IndexGeneratedDocumentsCommand;
use App\Models\Core\GeneratedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('indexes pdf files from disk', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Scan du disque')
        ->expectsOutputToContain('Module: commerce')
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);

    expect(GeneratedDocument::where('file_path', 'documents/commerce/devis/devis_001.pdf')->exists())->toBeTrue();
});

it('skips already indexed files without force flag', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    GeneratedDocument::factory()->create([
        'file_path' => 'documents/commerce/devis/devis_001.pdf',
    ]);

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);

    expect(GeneratedDocument::where('file_path', 'documents/commerce/devis/devis_001.pdf')->count())->toBe(1);
});

it('reindexes files with force flag', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    GeneratedDocument::factory()->create([
        'file_path' => 'documents/commerce/devis/devis_001.pdf',
        'file_name' => 'Old Name',
    ]);

    $this->artisan(IndexGeneratedDocumentsCommand::class, ['--force' => true])
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);

    expect(GeneratedDocument::where('file_path', 'documents/commerce/devis/devis_001.pdf')->count())->toBe(1);
});

it('filters by module option', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    Storage::disk('public')->makeDirectory('documents/rh/contrat');
    Storage::disk('public')->put('documents/rh/contrat/contrat_001.pdf', 'fake-pdf-content');

    $this->artisan(IndexGeneratedDocumentsCommand::class, ['--module' => 'rh'])
        ->expectsOutputToContain('Module: rh')
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);

    expect(GeneratedDocument::where('file_path', 'documents/rh/contrat/contrat_001.pdf')->exists())->toBeTrue()
        ->and(GeneratedDocument::where('file_path', 'documents/commerce/devis/devis_001.pdf')->exists())->toBeFalse();
});

it('skips doe_temp directory', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/doe_temp');
    Storage::disk('public')->put('documents/doe_temp/file.pdf', 'fake-pdf-content');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Scan du disque')
        ->doesntExpectOutputToContain('Module: doe_temp')
        ->assertExitCode(0);

    expect(GeneratedDocument::count())->toBe(0);
});

it('skips non-existing files gracefully', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    Storage::disk('public')->makeDirectory('documents/commerce/factures');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);
});

it('maps module names correctly', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/flotte/vehicules');
    Storage::disk('public')->put('documents/flotte/vehicules/vehicule_001.pdf', 'fake-pdf-content');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Module: flottes')
        ->assertExitCode(0);

    expect(GeneratedDocument::where('module', 'flottes')->exists())->toBeTrue();
});

it('handles empty documents directory', function () {
    Storage::fake('public');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Aucun dossier trouvé dans documents/')
        ->assertExitCode(0);
});

it('indexes multiple pdf files recursively', function () {
    Storage::fake('public');

    Storage::disk('public')->makeDirectory('documents/commerce/devis');
    Storage::disk('public')->put('documents/commerce/devis/devis_001.pdf', 'fake-pdf-content');

    Storage::disk('public')->makeDirectory('documents/commerce/factures');
    Storage::disk('public')->put('documents/commerce/factures/facture_001.pdf', 'fake-pdf-content');

    Storage::disk('public')->makeDirectory('documents/rh/contrats');
    Storage::disk('public')->put('documents/rh/contrats/contrat_001.pdf', 'fake-pdf-content');

    $this->artisan(IndexGeneratedDocumentsCommand::class)
        ->expectsOutputToContain('Indexation terminée')
        ->assertExitCode(0);

    expect(GeneratedDocument::count())->toBe(3);
});
