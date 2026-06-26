<?php

use App\Jobs\Core\RefreshCoreCacheJob;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('refresh cache job can be instantiated', function () {
    $job = new RefreshCoreCacheJob;

    expect($job)->not->toBeNull();
});

test('refresh cache job has handle method', function () {
    $job = new RefreshCoreCacheJob;

    expect(method_exists($job, 'handle'))->toBeTrue();
});

test('job can be dispatched', function () {
    dispatch(new RefreshCoreCacheJob);

    expect(true)->toBeTrue(); // Job dispatches without error
});
