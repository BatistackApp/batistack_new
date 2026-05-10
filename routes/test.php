<?php
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    dd(app(\App\Services\Core\SirenService::class)->getInformation('95157725300010'));
});
