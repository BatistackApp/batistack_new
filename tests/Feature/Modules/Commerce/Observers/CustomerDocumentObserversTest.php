<?php

namespace Tests\Feature\Modules\Commerce\Observers;

use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Jobs\Commerce\GenerateDocumentJob;
use Illuminate\Support\Facades\Queue;

describe('Customer Document Observers', function () {
    beforeEach(function () {
        Queue::fake();
    });

    it('generates order pdf on confirmed status', function () {
        $order = CustomerOrder::factory()->create(['status' => OrderStatus::DRAFT]);

        $order->update(['status' => OrderStatus::CONFIRMED]);

        Queue::assertPushed(GenerateDocumentJob::class, function ($job) use ($order) {
            return $job->namespace === 'order' && $job->model->id === $order->id;
        });
    });
});
