<?php

namespace App\Observers\Core;

use App\Models\Core\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        Cache::forget("core_setting_{$setting->key}");
    }

    public function deleted(Setting $setting): void
    {
        Cache::forget("core_setting_{$setting->key}");
    }
}
