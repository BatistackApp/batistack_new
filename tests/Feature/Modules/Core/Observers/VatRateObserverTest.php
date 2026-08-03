<?php

use App\Models\Core\VatRate;

beforeEach(function () {
    $this->vat = VatRate::factory()->create([
        'rate' => 20.0,
    ]);
});

test('vat rate observer validates percentage', function () {
    expect((float) $this->vat->rate)->toBe(20.0)
        ->and($this->vat->rate)->toBeGreaterThanOrEqual(0)
        ->and($this->vat->rate)->toBeLessThanOrEqual(100);
});

test('vat rate can be updated', function () {
    $this->vat->update(['rate' => 21.0]);

    expect((float) VatRate::find($this->vat->id)->rate)->toBe(21.0);
});

test('multiple vat rates can exist', function () {
    VatRate::factory()->create(['rate' => 5.5]);

    expect(VatRate::count())->toBe(2);
});

test('vat rate is numeric', function () {
    expect($this->vat->rate)->toBeNumeric();
});

test('rate must be between 0 and 100', function () {
    expect(function () {
        VatRate::factory()->create(['rate' => -1]);
    })->toThrow(\Exception::class, 'entre 0 et 100');

    expect(function () {
        VatRate::factory()->create(['rate' => 101]);
    })->toThrow(\Exception::class, 'entre 0 et 100');
});

test('setting a default vat rate updates others to false', function () {
    $first = VatRate::factory()->create(['rate' => 10, 'is_default' => true]);
    $second = VatRate::factory()->create(['rate' => 20, 'is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

test('cannot delete vat rate used by articles', function () {
    \Illuminate\Support\Facades\DB::shouldReceive('table')->andReturnUsing(function ($table) {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->andReturnSelf();
        $mockQuery->shouldReceive('count')->andReturn($table === 'articles' ? 1 : 0);
        return $mockQuery;
    });

    expect(function () {
        $this->vat->delete();
    })->toThrow(\Exception::class, 'articles l\'utilisent');
});

test('cache is forgotten on operations', function () {
    \Illuminate\Support\Facades\DB::shouldReceive('table')->andReturnUsing(function ($table) {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->andReturnSelf();
        $mockQuery->shouldReceive('count')->andReturn(0);
        return $mockQuery;
    });

    \Illuminate\Support\Facades\Cache::shouldReceive('forget')->with('vat_rates_all')->times(2);
    \Illuminate\Support\Facades\Cache::shouldReceive('forget')->with('vat_rate_default')->times(2); // updated, deleted
    \Illuminate\Support\Facades\Cache::shouldReceive('forget')->with("core_vat_rate_{$this->vat->id}")->times(1); // updated
    
    $this->vat->update(['is_default' => true]);
    $this->vat->delete();
});
