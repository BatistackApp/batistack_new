<?php

namespace Tests\Feature\Modules\Customer;

use Illuminate\Database\Eloquent\Model;

class DummyOrderModel extends Model
{
    protected $table = 'dummy_order_table';

    protected $guarded = [];
}
