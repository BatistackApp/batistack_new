<?php

namespace App\Services\RH;

use App\Models\RH\Interview;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class InterviewPdfService
{
    public function generatePdf(Interview $interview): string
    {
        $html = View::make('pdfs.interview-report', compact('interview'))->render();

        $fileName = 'interview_'.$interview->id.'_'.Str::random(10).'.pdf';
        $filePath = storage_path('app/public/'.$fileName);

        // Uses Spatie Browsershot (assuming Node and Puppeteer are available)
        Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->save($filePath);

        $media = $interview->addMedia($filePath)
            ->toMediaCollection('interviews');

        return $media->getPath();
    }
}
