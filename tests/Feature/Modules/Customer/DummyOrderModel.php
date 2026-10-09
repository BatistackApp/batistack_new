<?php

namespace Tests\Feature\Modules\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DummyOrderModel extends Model
{
    protected $table = 'dummy_order_table';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(DummyOrderModel::class, 'customer_order_id');
    }
}
