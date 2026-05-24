<?php

namespace App\Services\Core;

use Docuseal\Api;

class DocusealService
{
    protected Api $client;
    protected string $endpoint;

    public function __construct()
    {
        $this->client = new Api('API_KEY', config('services.docuseal.api_key'));
        $this->endpoint = config('services.docuseal.endpoint');
    }

    public function createSubmission(array $data = [])
    {
        try {
            return $this->client->createSubmissionFromPdf($data);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
