<?php

namespace App\Observers\Core;

use App\Models\Core\Company;
use Illuminate\Support\Facades\Cache;

class CompanyObserver
{
    public function saved(Company $company): void
    {
        Cache::forget('core_company_singleton');
    }
}
