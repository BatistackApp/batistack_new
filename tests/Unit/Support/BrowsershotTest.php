<?php

use App\Support\Browsershot;
use Spatie\Browsershot\Exceptions\FileUrlNotAllowed;

test('accepte une URL dont l\u2019h\u00f4te contient un underscore', function () {
    $browsershot = Browsershot::url('https://batistack_new.test/bim-viewer-headless/1');

    expect($browsershot)->toBeInstanceOf(Browsershot::class);
});

test('accepte une URL standard', function () {
    expect(Browsershot::url('https://example.com/page'))->toBeInstanceOf(Browsershot::class);
});

test('refuse toujours les protocoles dangereux', function (string $url) {
    expect(fn () => Browsershot::url($url))->toThrow(FileUrlNotAllowed::class);
})->with([
    'file:///etc/passwd',
    'file:/etc/passwd',
    'view-source:https://example.com',
]);

test('refuse une URL malform\u00e9e', function () {
    expect(fn () => Browsershot::url('not a url'))->toThrow(FileUrlNotAllowed::class, 'not a valid URL');
});
