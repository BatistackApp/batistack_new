<?php

use App\Filament\Articles\Resources\Items\ItemResource;
use App\Filament\Articles\Resources\Items\Pages\ListItems;
use App\Models\Articles\Item;
use App\Services\Articles\ArticleDocumentService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

it('can print a4 labels for selected items', function () {
    $items = Item::factory()->count(3)->create();

    // Mock the service to avoid calling Browsershot in tests
    $mockPath = 'documents/articles/mock_labels.pdf';
    Storage::disk('local')->put($mockPath, 'mock pdf content');

    $this->mock(ArticleDocumentService::class, function (MockInterface $mock) use ($mockPath, $items) {
        $mock->shouldReceive('generateLabels')
            ->once()
            ->withArgs(function ($records, $format, $copies) {
                return $records->count() === 3 && $format === 'a4' && $copies === 2;
            })
            ->andReturn($mockPath);

        $mock->shouldReceive('download')
            ->once()
            ->with($mockPath)
            ->andReturn(response()->download(storage_path('app/private/' . $mockPath)));
    });

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('articles'));

    \Livewire\Livewire::test(ListItems::class)
        ->assertTableBulkActionExists('printLabels')
        ->callTableBulkAction('printLabels', $items, [
            'format' => 'a4',
            'copies' => 2,
        ])
        ->assertSuccessful();
});

it('can print thermal dymo labels', function () {
    $item = Item::factory()->create();

    $mockPath = 'documents/articles/mock_labels_thermal.pdf';
    Storage::disk('local')->put($mockPath, 'mock pdf content');

    $this->mock(ArticleDocumentService::class, function (MockInterface $mock) use ($mockPath, $item) {
        $mock->shouldReceive('generateLabels')
            ->once()
            ->withArgs(function ($records, $format, $copies) {
                return $records->count() === 1 && $format === 'dymo_28_89' && $copies === 1;
            })
            ->andReturn($mockPath);

        $mock->shouldReceive('download')
            ->once()
            ->with($mockPath)
            ->andReturn(response()->download(storage_path('app/private/' . $mockPath)));
    });

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('articles'));

    \Livewire\Livewire::test(ListItems::class)
        ->callTableBulkAction('printLabels', [$item], [
            'format' => 'dymo_28_89',
            'copies' => 1,
        ])
        ->assertSuccessful();
});
