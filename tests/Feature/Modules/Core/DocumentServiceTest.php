<?php

use App\Services\Core\DocumentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

it('generates a pdf document', function () {
    Storage::fake('public');

    // Create a dummy view file for testing
    if (! is_dir(resource_path('views/testing'))) {
        mkdir(resource_path('views/testing'), 0777, true);
    }
    file_put_contents(resource_path('views/testing/dummy.blade.php'), '<h1>Hello Testing</h1>');

    $service = new DocumentService;

    try {
        $path = $service->generate('testing.dummy', [], 'test_file', 'reports');

        expect(Storage::disk('public')->exists('documents/reports/test_file.pdf'))->toBeTrue();
        expect($path)->toBe('documents/reports/test_file.pdf');
    } catch (CouldNotTakeBrowsershot $e) {
        $this->markTestSkipped('Browsershot/Puppeteer is not available on this system.');
    } catch (Exception $e) {
        // If node/npm binary is not found, also skip
        if (str_contains($e->getMessage(), 'The command') || str_contains($e->getMessage(), 'node')) {
            $this->markTestSkipped('Node is not available on this system.');
        } else {
            throw $e;
        }
    } finally {
        @unlink(resource_path('views/testing/dummy.blade.php'));
    }
});
