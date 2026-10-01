<?php

namespace Tests\Feature;

use App\Services\RH\GoogleCloudVisionOcrService;

class TestableOcrService extends GoogleCloudVisionOcrService
{
    public $mockClient;

    protected function createClient(array $clientConfig)
    {
        return $this->mockClient;
    }
}
