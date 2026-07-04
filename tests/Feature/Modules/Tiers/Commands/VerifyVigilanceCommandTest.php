<?php

namespace Tests\Feature\Modules\Tiers\Commands;

use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use Illuminate\Support\Facades\Queue;

it('dispatches VerifyGloabVigilanceJob and displays output', function () {
    Queue::fake();

    $this->artisan('tiers:verify-vigilance')
        ->expectsOutput('Lancement du scan de vigilance...')
        ->expectsOutput('Job de vigilance envoyé en file d\'attente.')
        ->assertSuccessful();

    Queue::assertPushed(VerifyGloabVigilanceJob::class);
});
