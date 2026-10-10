<?php

use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('allows multiple contacts for one account when they belong to the same company', function () {
    $user = User::factory()->create();
    $company = ThirdParty::factory()->create();

    DB::table('user_third_party_accounts')->insert([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'email' => null,
    ]));

    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'email' => null,
    ]));

    expect(Contact::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('rejects a contact linked to a different company for the same account at database level', function () {
    $user = User::factory()->create();
    $companyA = ThirdParty::factory()->create();
    $companyB = ThirdParty::factory()->create();

    DB::table('user_third_party_accounts')->insert([
        'user_id' => $user->id,
        'third_party_id' => $companyA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $companyA->id,
        'email' => null,
    ]));

    expect(fn () => Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $companyB->id,
        'email' => null,
    ])))->toThrow(QueryException::class);
});

it('keeps an existing account link when creating an additional contact for the same company', function () {
    $user = User::factory()->create();
    $company = ThirdParty::factory()->create();

    $firstContact = Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'email' => null,
    ]);

    $secondContact = Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'email' => 'additional-contact@example.test',
    ]);

    expect($firstContact->fresh()->user_id)->toBe($user->id)
        ->and($secondContact->fresh()->user_id)->toBe($user->id)
        ->and(User::query()->count())->toBe(1)
        ->and(Contact::query()->where('user_id', $user->id)->count())->toBe(2);
});
