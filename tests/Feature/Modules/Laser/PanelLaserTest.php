<?php

use App\Models\User;

it('renders the laser dashboard for admin', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/laser')
        ->assertOk();
});

it('denies non-admin from laser panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/laser')
        ->assertForbidden();
});
