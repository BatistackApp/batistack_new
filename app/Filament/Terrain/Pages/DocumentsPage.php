<?php

namespace App\Filament\Terrain\Pages;

use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierDocumentService;
use App\Services\Core\DocumentService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DocumentsPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Phosphor::FilePdf;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents de Chantier';

    protected static ?string $slug = 'documents-chantier';

    protected static UnitEnum|string|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.terrain.pages.documents-chantier';

    public ?Chantier $record = null;

    public function mount(mixed $record = null): void
    {
        if ($record) {
            $this->record = Chantier::find($record);
        }
    }

    public function getHeading(): string
    {
        return 'Documents — '.$this->record?->name;
    }

    public function downloadDocument(string $type): \Symfony\Component\HttpFoundation\Response
    {
        $record = $this->record;

        if (! $record) {
            abort(404, 'Aucun chantier sélectionné.');
        }

        $service = app(ChantierDocumentService::class);

        $path = match ($type) {
            'start_order' => $service->generateStartOrder($record),
            'rentability' => $service->generateRentabilityReport($record),
            'journal' => $service->generateWeeklyJournal($record, now()->startOfWeek()),
            'ppsps' => $service->generatePpsps($record),
            'pv' => $service->generateHandoverProtocol($record),
            default => null,
        };

        if (! $path) {
            abort(500, 'Erreur lors de la génération du document.');
        }

        $filename = class_basename($path);

        return Storage::disk(DocumentService::getDisk())->download($path, $filename);
    }
}
