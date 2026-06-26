<?php

use App\Jobs\Core\CreateDocumentJob;
use App\Models\Commerce\CustomerQuote;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->quote = CustomerQuote::factory()->create();
});

test('create document job is queueable', function () {
    Bus::fake();

    dispatch(new CreateDocumentJob('quote', $this->quote));

    Bus::assertDispatched(CreateDocumentJob::class);
});

test('create document job can be instantiated', function () {
    $job = new CreateDocumentJob('quote', $this->quote);

    expect($job)->not->toBeNull();
});

test('job has handle method', function () {
    $job = new CreateDocumentJob('quote', $this->quote);

    expect(method_exists($job, 'handle'))->toBeTrue();
});
