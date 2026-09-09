<?php

namespace App\Services\Laser;

use App\Models\Core\Company;
use App\Models\Laser\LaserQuote;
use App\Services\Core\DocumentService;
use Carbon\Carbon;

class LaserDocumentationService extends DocumentService
{
    public function generateQuotePdf(LaserQuote $quote): string
    {
        $quote->load(['client', 'lines.material']);

        $data = [
            'company' => Company::firstOrFail(),
            'quote' => $quote,
            'title' => 'DEVIS LASER N° '.$quote->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.quote',
            $data,
            $this->getQuoteFilename($quote),
            'laser/quotes',
            false,
            $quote,
            'laser_quote',
        );
    }

    public function getQuoteFilename(LaserQuote $quote): string
    {
        return 'devis_laser_'.$quote->reference;
    }

    public function getQuotePath(LaserQuote $quote): string
    {
        return 'laser/quotes/'.$this->getQuoteFilename($quote).'.pdf';
    }
}
