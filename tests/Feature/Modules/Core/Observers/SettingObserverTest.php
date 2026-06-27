<?php

use App\Models\Core\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->setting = Setting::factory()->create([
        'key' => 'app_name',
        'value' => 'Batistack',
    ]);
});

test('setting observer saves to database', function () {
    expect($this->setting->key)->toBe('app_name')
        ->and($this->setting->value)->toBe('Batistack');
});

test('setting can be updated', function () {
    $this->setting->update(['value' => 'Batistack Pro']);

    expect(Setting::where('key', 'app_name')->first()->value)
        ->toBe('Batistack Pro');
});

test('setting key must be unique', function () {
    expect(Setting::where('key', 'app_name')->count())->toBe(1);
});

test('multiple settings can coexist', function () {
    Setting::factory()->create(['key' => 'app_version']);

    expect(Setting::count())->toBe(2);
});
