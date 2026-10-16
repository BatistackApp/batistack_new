<?php

namespace Tests\Feature\Modules\Customer;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use Filament\Resources\Resource;

class DummySituationResource extends Resource
{
    protected static ?string $model = DummySituationModel::class;

    use ScopesToAuthenticatedThirdParty;
}
