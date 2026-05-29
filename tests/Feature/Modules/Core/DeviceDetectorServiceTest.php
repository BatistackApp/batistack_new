<?php

namespace Tests\Feature\Modules\Core;

use App\Services\Core\DeviceDetectorService;
use Illuminate\Http\Request;

beforeEach(function () {
    //
});

describe('DeviceDetectorService - isMobile', function () {
    test('détecte un iPhone', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeTrue();
    });

    test('détecte un Android mobile', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeTrue();
    });

    test('détecte un BlackBerry', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (BlackBerry; U; BlackBerry 9800)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeTrue();
    });

    test('retourne false pour un desktop', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeFalse();
    });

    test('détecte Firefox mobile', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Android 11; Mobile; rv:89.0)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeTrue();
    });

    test('gère un user agent vide', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => '',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isMobile())->toBeFalse();
    });
});

describe('DeviceDetectorService - isTablet', function () {
    test('détecte un iPad', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeTrue();
    });

    test('détecte une tablette Android', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 11; SM-T870)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeTrue();
    });

    test('détecte un Kindle', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; U; Android 2.3; en-us; Kindle Fire)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeTrue();
    });

    test('retourne false pour un mobile', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeFalse();
    });

    test('retourne false pour un desktop', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeFalse();
    });

    test('détecte un PlayBook', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (PlayBook; U; RIM Tablet OS 2.0.0)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isTablet())->toBeTrue();
    });
});

describe('DeviceDetectorService - isDesktop', function () {
    test('détecte un ordinateur Windows', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isDesktop())->toBeTrue();
    });

    test('détecte un ordinateur macOS', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isDesktop())->toBeTrue();
    });

    test('détecte un ordinateur Linux', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isDesktop())->toBeTrue();
    });

    test('retourne false pour un mobile', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isDesktop())->toBeFalse();
    });

    test('retourne false pour une tablette', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->isDesktop())->toBeFalse();
    });
});

describe('DeviceDetectorService - getDeviceType', function () {
    test('retourne "mobile" pour un mobile', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('mobile');
    });

    test('retourne "tablet" pour une tablette', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('tablet');
    });

    test('retourne "desktop" pour un ordinateur', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('desktop');
    });

    test('priorise mobile sur tablet', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('mobile')
            ->and($service->isMobile())->toBeTrue()
            ->and($service->isTablet())->toBeFalse();
    });

    test('retourne "desktop" par défaut pour un user agent inconnu', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'UnknownBrowser/1.0',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('desktop');
    });

    test('gère un user agent vide', function () {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => '',
        ]);

        $service = new DeviceDetectorService($request);

        expect($service->getDeviceType())->toBe('desktop');
    });
});
