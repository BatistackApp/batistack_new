<?php

namespace Tests\Feature\Modules\Commerce\Observers;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerOrder;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\DeliveryNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

describe('Customer Document Observers', function () {
    beforeEach(function () {
        Queue::fake();
    });

    it('generates order pdf on confirmed status', function () {
        $serviceMock = Mockery::mock(CommerceDocumentationService::class);
        $serviceMock->shouldReceive('generateOrderPdf')->once();
        app()->instance(CommerceDocumentationService::class, $serviceMock);

        $order = CustomerOrder::factory()->create(['status' => OrderStatus::DRAFT]);
        
        $order->update(['status' => OrderStatus::CONFIRMED]);
    });

    it('generates delivery note pdf on shipped status', function () {
        \App\Models\Core\Setting::factory()->create();
        $serviceMock = Mockery::mock(\App\Services\Commerce\CommerceDocumentationService::class);
        $serviceMock->shouldReceive('generateDeliveryNotePdf')->once();
        // The observer triggers GenerateDocumentJob which uses CommerceDocumentationService!
        app()->instance(\App\Services\Commerce\CommerceDocumentationService::class, $serviceMock);

        $client = ThirdParty::factory()->create();
        Contact::factory()->create(['third_party_id' => $client->id, 'is_primary' => true]);

        $deliveryNote = CustomerDeliveryNote::factory()->create(['status' => DeliveryStatus::PREPARATION, 'client_id' => $client->id]);
        
        $deliveryNote->update(['status' => DeliveryStatus::SHIPPED]);
    });
});
