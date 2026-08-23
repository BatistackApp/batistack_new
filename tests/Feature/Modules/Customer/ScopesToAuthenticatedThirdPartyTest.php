<?php

namespace Tests\Feature\Customer;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

class DummyModel extends Model
{
    protected $table = 'dummy_table';

    protected $guarded = [];
}

class DummyResource extends \Filament\Resources\Resource
{
    protected static ?string $model = DummyModel::class;

    use ScopesToAuthenticatedThirdParty;
}

beforeEach(function () {
    Schema::create('dummy_table', function ($table) {
        $table->id();
        $table->foreignId('third_party_id')->nullable();
        $table->timestamps();
    });
});

it('scopes query to authenticated user contact third party', function () {
    $user = User::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
    ]));

    $otherThirdParty = ThirdParty::factory()->create();

    DummyModel::create(['third_party_id' => $thirdParty->id]);
    DummyModel::create(['third_party_id' => $otherThirdParty->id]);

    $this->actingAs($user);

    $results = DummyResource::getEloquentQuery()->get();

    expect($results->count())->toBe(1);
    expect($results->first()->third_party_id)->toBe($thirdParty->id);
});

it('returns empty query if authenticated user has no contact', function () {
    $user = User::factory()->create();

    $thirdParty = ThirdParty::factory()->create();
    DummyModel::create(['third_party_id' => $thirdParty->id]);

    $this->actingAs($user);

    $results = DummyResource::getEloquentQuery()->get();

    expect($results->count())->toBe(0);
});
