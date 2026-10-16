<?php

namespace Tests\Feature\Modules\Customer;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use Filament\Resources\Resource;

class DummyClientResource extends Resource
{
    protected static ?string $model = DummyClientModel::class;

    use ScopesToAuthenticatedThirdParty;
}
