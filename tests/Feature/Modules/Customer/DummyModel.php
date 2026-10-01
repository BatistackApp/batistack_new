<?php

namespace Tests\Feature\Modules\Customer;

use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    protected $table = 'dummy_table';

    protected $guarded = [];
}
