<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may still need some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function signedLaserQuote(\App\Models\Laser\LaserQuote $quote): \App\Models\Laser\LaserQuote
{
    $quote->update([
        'status' => \App\Enums\Laser\QuoteStatus::ACCEPTED,
        'signed_at' => now(),
    ]);

    $provider = app(\App\Services\Core\Providers\LocalSignatureProvider::class);
    $method = new \ReflectionMethod($provider, 'generateChecksum');
    $method->setAccessible(true);

    $signature = \App\Models\Core\Signature::create([
        'token' => \Illuminate\Support\Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'user_id' => null,
        'status' => \App\Enums\Core\SignatureStatus::SIGNED,
        'type' => \App\Enums\Core\SignatureType::AUTOGRAPH,
        'signature_data' => 'test-signature',
        'checksum' => $method->invoke($provider, $quote),
        'signed_at' => $quote->signed_at,
        'metadata' => ['provider' => 'local'],
    ]);

    app(\App\Services\Core\SignatureService::class)->refreshChecksum($signature->fresh());

    return $quote->fresh();
}
