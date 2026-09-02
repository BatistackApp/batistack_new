<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Vision3D\BimModel;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class BimViewerPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Phosphor::Cube;

    protected static ?string $navigationLabel = 'Maquettes 3D';

    protected static ?string $title = 'Maquettes BIM / Plans';

    protected static ?string $slug = 'bim-viewer';

    protected static UnitEnum|string|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.terrain.pages.bim-viewer';

    public array $chantiers = [];

    public array $models = [];

    public ?int $selectedModelId = null;

    public function mount(): void
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return;
        }

        $this->chantiers = Chantier::forEmployee($employee)
            ->whereIn('status', [
                ChantierStatus::IN_PROGRESS,
                ChantierStatus::AWAITING_RECEPTION,
                ChantierStatus::PLANNED,
            ])
            ->with(['bimModels' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->toArray();

        // Collect all models
        $this->models = collect($this->chantiers)
            ->flatMap(fn ($c) => collect($c['bim_models'] ?? [])->map(fn ($m) => [...$m, 'chantier_name' => $c['name'], 'chantier_id' => $c['id']]))
            ->toArray();
    }

    public function selectModel(int $modelId): void
    {
        $this->selectedModelId = $modelId;
    }

    public function getModelUrl(): ?string
    {
        if (! $this->selectedModelId) {
            return null;
        }

        $model = BimModel::find($this->selectedModelId);

        if (! $model || ! $model->file_path) {
            return null;
        }

        return Storage::disk('public')->url($model->file_path);
    }

    public function getViewerUrl(): ?string
    {
        if (! $this->selectedModelId) {
            return null;
        }

        return "/bim-viewer-headless/{$this->selectedModelId}";
    }
}
