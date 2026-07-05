<?php

namespace Tests\Feature\Modules\Chantiers;

use App\Enums\Chantiers\DoeDocumentCategory;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\DoeDocument;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Chantiers\DoeDocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->service = Mockery::mock(DoeDocumentService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $this->service->shouldReceive('generateSommairePdf')->andReturnUsing(function () {
        $path = 'chantiers/doe_temp/dummy_sommaire.pdf';
        Storage::disk('public')->put($path, 'dummy content');
        return $path;
    });

    $this->chantier = Chantier::factory()->create();
    $this->chantier->client()->associate(ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]));
    $this->chantier->save();
    $this->chantier->load(['client', 'manager']);
});

describe('DoeDocumentService - compileDoe', function () {
    test('compile le DOE d\'un chantier', function () {
        // Créer un document validé via une requête directe
        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::AUTRE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('DOE_')
            ->and($path)->toEndWith('.zip');
    });

    test('crée un fichier ZIP', function () {
        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect(file_exists($path))->toBeTrue();
    });

    test('organise les fichiers dans chantiers/doe', function () {
        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('chantiers/doe');
    });

    test('inclut la référence du chantier dans le nom du ZIP', function () {
        $this->chantier->update(['reference' => 'CH-2026-001']);

        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('ch-2026-001');
    });

    test('lève une exception si aucun document validé', function () {
        expect(function () {
            $this->service->compileDoe($this->chantier);
        })->toThrow(\Exception::class, "Aucun document ou fiche technique");
    });

    test('ignore les documents non validés', function () {
        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => false,
        ]);

        expect(function () {
            $this->service->compileDoe($this->chantier);
        })->toThrow(\Exception::class);
    });

    test('inclut plusieurs documents validés', function () {
        \DB::table('doe_documents')->insert([
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Document 1',
                'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Document 2',
                'category' => DoeDocumentCategory::CONFORMITE,
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('charge les relations du chantier', function () {
        $this->chantier->load(['client', 'manager']);

        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('inclut un sommaire dans le ZIP', function () {
        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        $zip = new \ZipArchive;
        $zip->open($path);

        expect($zip->locateName('00_SOMMAIRE_OFFICIEL.pdf'))->not->toBeFalse();

        $zip->close();
    });

    test('organise les documents par catégorie', function () {
        \DB::table('doe_documents')->insert([
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Doc Technique',
                'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Doc Admin',
                'category' => DoeDocumentCategory::CONFORMITE,
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('inclut la timestamp dans le nom du ZIP', function () {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        DoeDocument::create([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => DoeDocumentCategory::FICHE_TECHNIQUE,
            'is_validated' => true,
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain($now->format('Ymd_His'));

        Carbon::setTestNow();
    });
});
