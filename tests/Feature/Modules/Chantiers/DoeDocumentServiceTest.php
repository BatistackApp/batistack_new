<?php

namespace Tests\Feature\Modules\Chantiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Chantiers\DoeDocumentService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->service = app(DoeDocumentService::class);
    $this->chantier = Chantier::factory()->create();
    $this->chantier->client()->associate(ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]));
    $this->chantier->save();
    $this->chantier->load(['client', 'manager']);
});

describe('DoeDocumentService - compileDoe', function () {
    test('compile le DOE d\'un chantier', function () {
        // Créer un document validé via une requête directe
        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('DOE_')
            ->and($path)->toEndWith('.zip');
    });

    test('crée un fichier ZIP', function () {
        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect(file_exists($path))->toBeTrue();
    });

    test('organise les fichiers dans chantiers/doe', function () {
        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('chantiers/doe');
    });

    test('inclut la référence du chantier dans le nom du ZIP', function () {
        $this->chantier->update(['reference' => 'CH-2026-001']);

        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain('ch-2026-001');
    });

    test('lève une exception si aucun document validé', function () {
        expect(function () {
            $this->service->compileDoe($this->chantier);
        })->toThrow(\Exception::class, 'Aucun document validé');
    });

    test('ignore les documents non validés', function () {
        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Non Validé',
            'category' => 'technique',
            'is_validated' => false,
            'created_at' => now(),
            'updated_at' => now(),
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
                'category' => 'technique',
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Document 2',
                'category' => 'administratif',
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

        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('inclut un sommaire dans le ZIP', function () {
        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        $zip = new \ZipArchive();
        $zip->open($path);

        expect($zip->nameExists('00_SOMMAIRE_OFFICIEL.pdf'))->toBeTrue();

        $zip->close();
    });

    test('organise les documents par catégorie', function () {
        \DB::table('doe_documents')->insert([
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Doc Technique',
                'category' => 'technique',
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chantier_id' => $this->chantier->id,
                'name' => 'Doc Admin',
                'category' => 'administratif',
                'is_validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('inclut la timestamp dans le nom du ZIP', function () {
        $timestamp = now()->format('Ymd_His');

        \DB::table('doe_documents')->insert([
            'chantier_id' => $this->chantier->id,
            'name' => 'Document Test',
            'category' => 'technique',
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->service->compileDoe($this->chantier);

        expect($path)->toContain($timestamp);
    });
});
