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

test('validation fails for invalid integer', function () {
    expect(function () {
        Setting::factory()->create(['key' => 'test_int', 'type' => 'integer', 'value' => 'not an int']);
    })->toThrow(Exception::class, 'entier');
});

test('validation fails for invalid boolean', function () {
    expect(function () {
        Setting::factory()->create(['key' => 'test_bool', 'type' => 'boolean', 'value' => 'not a bool']);
    })->toThrow(Exception::class, 'booléen');
});

test('validation fails for invalid email', function () {
    expect(function () {
        Setting::factory()->create(['key' => 'test_email', 'type' => 'email', 'value' => 'not-an-email']);
    })->toThrow(Exception::class, 'invalide');
});

test('validation fails for invalid url', function () {
    expect(function () {
        Setting::factory()->create(['key' => 'test_url', 'type' => 'url', 'value' => 'not-a-url']);
    })->toThrow(Exception::class, 'invalide');
});

test('validation fails for invalid array', function () {
    expect(function () {
        Setting::factory()->create(['key' => 'test_array', 'type' => 'array', 'value' => 'not-json']);
    })->toThrow(Exception::class, 'JSON');
});

test('cannot delete critical setting', function () {
    expect(function () {
        $this->setting->delete();
    })->toThrow(Exception::class, 'critique');
});

test('cache is forgotten on operations', function () {
    Cache::shouldReceive('forget')->with('core_setting_test_cache')->times(3);
    Cache::shouldReceive('forget')->with('core_settings_all')->times(3);

    $setting = Setting::factory()->create(['key' => 'test_cache', 'value' => 'v1']);
    $setting->update(['value' => 'v2']);
    $setting->delete();
});
