<?php

use App\Services\Core\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function simplePdfView(): void
{
    $dir = sys_get_temp_dir().'/batistack_pdf_test_'.uniqid();
    @mkdir($dir);
    file_put_contents($dir.'/simple.blade.php', '<html><body><h1>{{ $title ?? "PDF" }}</h1></body></html>');

    View::addLocation($dir);
}

function browsershotChain(string $pdfBehaviour): object
{
    $mock = Mockery::mock(Browsershot::class);
    $mock->shouldReceive('setNodeBinary')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('setNpmBinary')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('showBackground')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('waitUntilNetworkIdle')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('noSandbox')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('format')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('margins')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('landscape')->withAnyArgs()->andReturnSelf();
    $mock->shouldReceive('setChromePath')->withAnyArgs()->andReturnSelf();

    if ($pdfBehaviour === 'throw') {
        $process = new Process(['php', '-r', 'exit(1);']);
        $process->run();
        $mock->shouldReceive('pdf')->andThrow(new ProcessFailedException($process));
    } else {
        $mock->shouldReceive('pdf')->andReturn('%PDF-1.7 mock content');
    }

    return $mock;
}

function fakeDocumentService(string $pdfBehaviour): DocumentService
{
    return new class($pdfBehaviour) extends DocumentService
    {
        public function __construct(private string $behaviour) {}

        protected function makeBrowsershot(string $html): Browsershot
        {
            return $this->behaviour === 'throw'
                ? browsershotChain('throw') : browsershotChain('ok');
        }
    };
}

it('wraps a browsershot failure into a RuntimeException and keeps previous', function () {
    simplePdfView();

    $service = fakeDocumentService('throw');

    $previous = null;
    try {
        $service->generate('simple', ['title' => 'Test'], 'contrat_test_'.Str::random(4), 'rh');
    } catch (RuntimeException $e) {
        $previous = $e->getPrevious();
        expect($e->getMessage())->toContain('La génération du PDF a échoué');
    }

    expect($previous)->toBeInstanceOf(ProcessFailedException::class);
});

it('returns a valid path after successful pdf generation', function () {
    simplePdfView();

    $service = fakeDocumentService('ok');
    $path = $service->generate('simple', ['title' => 'Test'], 'ok_'.Str::random(4), 'rh');

    expect($path)->toBeString()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    Storage::disk('public')->delete($path);
});
