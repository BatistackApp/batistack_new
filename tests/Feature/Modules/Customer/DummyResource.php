<?php

namespace Tests\Feature\Modules\Customer;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use Filament\Resources\Resource;

class DummyResource extends Resource
{
    protected static ?string $model = DummyModel::class;

    use ScopesToAuthenticatedThirdParty;
}
