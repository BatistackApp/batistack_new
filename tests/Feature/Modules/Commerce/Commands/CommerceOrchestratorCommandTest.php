<?php

use App\Jobs\Commerce\CheckExpiredQuotesJob;
use App\Jobs\Commerce\CheckOverdueInvoicesJob;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

test('orchestrator runs all checks by default', function () {
    $this->artisan('commerce:orchestrator')
        ->expectsOutputToContain('Lancement du scan des devis expirés')
        ->expectsOutputToContain('Lancement du scan des factures impayées')
        ->assertExitCode(0);

    Queue::assertPushed(CheckExpiredQuotesJob::class);
    Queue::assertPushed(CheckOverdueInvoicesJob::class);
});

test('orchestrator runs all checks with --all flag', function () {
    $this->artisan('commerce:orchestrator', ['--all' => true])
        ->expectsOutputToContain('Lancement du scan des devis expirés')
        ->expectsOutputToContain('Lancement du scan des factures impayées')
        ->assertExitCode(0);

    Queue::assertPushed(CheckExpiredQuotesJob::class);
    Queue::assertPushed(CheckOverdueInvoicesJob::class);
});

test('orchestrator runs only quote check with --check-quotes flag', function () {
    $this->artisan('commerce:orchestrator', ['--check-quotes' => true])
        ->expectsOutputToContain('Lancement du scan des devis expirés')
        ->doesntExpectOutputToContain('Lancement du scan des factures impayées')
        ->assertExitCode(0);

    Queue::assertPushed(CheckExpiredQuotesJob::class);
    Queue::assertNotPushed(CheckOverdueInvoicesJob::class);
});

test('orchestrator runs only invoice check with --check-invoices flag', function () {
    $this->artisan('commerce:orchestrator', ['--check-invoices' => true])
        ->doesntExpectOutputToContain('Lancement du scan des devis expirés')
        ->expectsOutputToContain('Lancement du scan des factures impayées')
        ->assertExitCode(0);

    Queue::assertNotPushed(CheckExpiredQuotesJob::class);
    Queue::assertPushed(CheckOverdueInvoicesJob::class);
});
