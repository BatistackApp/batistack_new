<?php

namespace Tests\Feature\Modules\Customer;

use Illuminate\Database\Eloquent\Model;

class DummyClientModel extends Model
{
    protected $table = 'dummy_client_table';

    protected $guarded = [];
}
