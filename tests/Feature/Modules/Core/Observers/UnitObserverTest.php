<?php

use App\Models\Core\Unit;

beforeEach(function () {
    $this->unit = Unit::factory()->create([
        'name' => 'Mètre',
        'symbol' => 'm',
    ]);
});

test('unit observer creates reference', function () {
    expect($this->unit)
        ->not->toBeNull()
        ->and($this->unit->symbol)->toBe('m');
});

test('unit has valid name', function () {
    expect($this->unit->name)->not->toBeEmpty()
        ->and($this->unit->name)->toBe('Mètre');
});

test('unit abbreviation is stored', function () {
    $stored = Unit::find($this->unit->id);

    expect($stored->symbol)->toBe('m');
});

test('units are countable', function () {
    Unit::factory()->create(['symbol' => 'km']);

    expect(Unit::count())->toBe(2);
});

test('creating empty unit name throws exception', function () {
    expect(function () {
        Unit::factory()->create(['name' => '']);
    })->toThrow(\Exception::class, 'obligatoire');
});

test('cannot delete unit used by articles', function () {
    \Illuminate\Support\Facades\DB::shouldReceive('table')->andReturnUsing(function ($table) {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->andReturnSelf();
        $mockQuery->shouldReceive('count')->andReturn($table === 'articles' ? 1 : 0);
        return $mockQuery;
    });

    expect(function () {
        $this->unit->delete();
    })->toThrow(\Exception::class, 'articles l\'utilisent');
});

test('cannot delete unit used by customer_order_items', function () {
    \Illuminate\Support\Facades\DB::shouldReceive('table')->andReturnUsing(function ($table) {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->andReturnSelf();
        $mockQuery->shouldReceive('count')->andReturn($table === 'customer_order_items' ? 1 : 0);
        return $mockQuery;
    });

    expect(function () {
        $this->unit->delete();
    })->toThrow(\Exception::class, 'lignes de commande l\'utilisent');
});

test('cache is forgotten on operations', function () {
    \Illuminate\Support\Facades\DB::shouldReceive('table')->andReturnUsing(function ($table) {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->andReturnSelf();
        $mockQuery->shouldReceive('count')->andReturn(0);
        return $mockQuery;
    });

    \Illuminate\Support\Facades\Cache::shouldReceive('forget')->with('units_all')->times(3);
    \Illuminate\Support\Facades\Cache::shouldReceive('forget')->with('unit_kg')->times(2); // updated, deleted
    
    $unit = Unit::factory()->create(['name' => 'Kilo', 'symbol' => 'kg']);
    $unit->update(['name' => 'Kilogramme']);
    $unit->delete();
});
