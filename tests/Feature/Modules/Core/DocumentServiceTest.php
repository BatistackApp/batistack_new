<?php

use App\Services\Core\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns default disk', function () {
    expect(DocumentService::getDisk())->toBe('public');
});

it('extracts module from path correctly', function () {
    $service = new DocumentService;

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractModuleFromPath');
    $method->setAccessible(true);

    expect($method->invoke($service, 'commerce/quotes'))->toBe('commerce')
        ->and($method->invoke($service, 'rh'))->toBe('rh')
        ->and($method->invoke($service, 'gpao/production/orders'))->toBe('gpao');
});
