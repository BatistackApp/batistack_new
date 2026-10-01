<?php

namespace Tests\Feature\Modules\Core;

use App\Traits\Core\HasSignature;
use Illuminate\Database\Eloquent\Model;

class TestModelWithoutOverrides extends Model
{
    use HasSignature;

    protected $table = 'customers';

    protected $fillable = ['name'];
}
